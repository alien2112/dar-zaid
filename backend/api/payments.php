<?php
require_once __DIR__ . '/../config/database.php';

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

// Create payments table if not exists with idempotency support
$db->exec("CREATE TABLE IF NOT EXISTS payments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    transaction_id VARCHAR(255) UNIQUE NOT NULL,
    order_id VARCHAR(255) NOT NULL,
    payment_method VARCHAR(50) NOT NULL,
    amount DECIMAL(10, 2) NOT NULL,
    currency VARCHAR(3) DEFAULT 'SAR',
    status ENUM('pending', 'processing', 'completed', 'failed', 'cancelled', 'refunded') DEFAULT 'pending',
    provider_transaction_id VARCHAR(255),
    provider_response JSON,
    customer_info JSON,
    payment_details JSON,
    idempotency_key VARCHAR(255),
    webhook_verified BOOLEAN DEFAULT FALSE,
    webhook_signature VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_transaction_id (transaction_id),
    INDEX idx_order_id (order_id),
    INDEX idx_status (status),
    INDEX idx_payment_method (payment_method),
    INDEX idx_idempotency_key (idempotency_key),
    INDEX idx_provider_transaction_id (provider_transaction_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

// Create books table if not exists with stock tracking
$db->exec("CREATE TABLE IF NOT EXISTS books (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    author VARCHAR(255) NOT NULL,
    description TEXT,
    price DECIMAL(10, 2) NOT NULL,
    category VARCHAR(100),
    image_url VARCHAR(500),
    isbn VARCHAR(20),
    stock_quantity INT NOT NULL DEFAULT 0,
    reserved_quantity INT NOT NULL DEFAULT 0,
    is_chosen BOOLEAN DEFAULT FALSE,
    published_date DATE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_title (title),
    INDEX idx_author (author),
    INDEX idx_category (category),
    INDEX idx_isbn (isbn),
    INDEX idx_stock (stock_quantity)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

// Create orders table if not exists with improved structure
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

$request_uri = $_SERVER['REQUEST_URI'];
$path_parts = explode('/', trim(parse_url($request_uri, PHP_URL_PATH), '/'));
$endpoint = $path_parts[count($path_parts) - 1] ?? '';

switch ($method) {
    case 'POST':
        if ($endpoint === 'initialize') {
            handlePaymentInitialization($db);
        } elseif ($endpoint === 'process') {
            handlePaymentProcessing($db);
        } elseif ($endpoint === 'callback') {
            handlePaymentCallback($db);
        } elseif ($endpoint === 'refund') {
            handlePaymentRefund($db);
        } else {
            http_response_code(404);
            echo json_encode(['error' => 'Endpoint not found'], JSON_UNESCAPED_UNICODE);
        }
        break;

    case 'GET':
        if (strpos($endpoint, 'verify') === 0) {
            $transaction_id = $path_parts[count($path_parts) - 1] ?? '';
            handlePaymentVerification($db, $transaction_id);
        } elseif ($endpoint === 'history') {
            handlePaymentHistory($db);
        } else {
            http_response_code(404);
            echo json_encode(['error' => 'Endpoint not found'], JSON_UNESCAPED_UNICODE);
        }
        break;

    default:
        http_response_code(405);
        echo json_encode(['error' => 'Method not allowed'], JSON_UNESCAPED_UNICODE);
}

function handlePaymentInitialization($db) {
    try {
        $data = json_decode(file_get_contents('php://input'), true);

        if (!$data || !isset($data['payment_method']) || !isset($data['amount'])) {
            http_response_code(400);
            echo json_encode(['error' => 'Payment method and amount are required'], JSON_UNESCAPED_UNICODE);
            return;
        }

        // Generate idempotency key if not provided
        if (!isset($data['idempotency_key'])) {
            $data['idempotency_key'] = 'idem_' . time() . '_' . uniqid();
        }

        $payment_method = $data['payment_method'];
        $amount = (float)$data['amount'];
        $currency = $data['currency'] ?? 'SAR';
        $order_id = $data['order_id'] ?? 'order_' . time();
        $transaction_id = 'txn_' . time() . '_' . uniqid();

        // Create order with transaction and stock validation
        $order_result = createOrderWithTransaction($db, $data);
        if (isset($order_result['existing']) && $order_result['existing']) {
            // Return existing order info for idempotent request
            echo json_encode([
                'status' => 'existing',
                'order_id' => $order_result['order_id'],
                'message' => 'Order already exists for this idempotency key'
            ], JSON_UNESCAPED_UNICODE);
            return;
        }

        // Create payment record
        $stmt = $db->prepare(
            'INSERT INTO payments (transaction_id, order_id, payment_method, amount, currency, status, customer_info, payment_details)
             VALUES (:transaction_id, :order_id, :payment_method, :amount, :currency, :status, :customer_info, :payment_details)'
        );

        $stmt->execute([
            'transaction_id' => $transaction_id,
            'order_id' => $order_id,
            'payment_method' => $payment_method,
            'amount' => $amount,
            'currency' => $currency,
            'status' => 'pending',
            'customer_info' => json_encode($data['customer_info'] ?? []),
            'payment_details' => json_encode($data)
        ]);

        // Route to specific payment provider
        $response = routeToPaymentProvider($payment_method, $transaction_id, $data);

        echo json_encode($response, JSON_UNESCAPED_UNICODE);

    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Database error: ' . $e->getMessage()], JSON_UNESCAPED_UNICODE);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Payment initialization error: ' . $e->getMessage()], JSON_UNESCAPED_UNICODE);
    }
}

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
                $book_id = (int)($item['id'] ?? 0);
                $quantity = (int)($item['quantity'] ?? 1);

                if ($book_id <= 0 || $quantity <= 0) {
                    throw new Exception('Invalid item ID or quantity');
                }

                // Lock the book row and check stock with FOR UPDATE
                $bookStmt = $db->prepare('SELECT id, title, price, stock_quantity FROM books WHERE id = :id FOR UPDATE');
                $bookStmt->execute(['id' => $book_id]);
                $book = $bookStmt->fetch(PDO::FETCH_ASSOC);

                if (!$book) {
                    throw new Exception('Book not found: ' . $book_id);
                }

                if ($book['stock_quantity'] < $quantity) {
                    throw new Exception('Insufficient stock for book: ' . $book['title'] . '. Available: ' . $book['stock_quantity']);
                }

                $item_total = (float)$book['price'] * $quantity;
                $calculated_subtotal += $item_total;

                $validated_items[] = [
                    'id' => $book_id,
                    'title' => $book['title'],
                    'price' => (float)$book['price'],
                    'quantity' => $quantity,
                    'total' => $item_total
                ];

                // Reserve stock (reduce immediately during order creation)
                $updateStmt = $db->prepare('UPDATE books SET stock_quantity = stock_quantity - :quantity WHERE id = :id');
                $updateStmt->execute(['quantity' => $quantity, 'id' => $book_id]);
            }
        }

        $shipping_cost = (float)($data['shipping_cost'] ?? 0);
        $tax_amount = (float)($data['tax_amount'] ?? 0);
        $calculated_total = $calculated_subtotal + $shipping_cost + $tax_amount;

        // Validate client-provided total matches server calculation
        $client_total = (float)($data['total'] ?? 0);
        if (abs($calculated_total - $client_total) > 0.01) {
            throw new Exception('Total amount mismatch. Expected: ' . $calculated_total . ', Received: ' . $client_total);
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
        return ['order_id' => $order_id, 'status' => 'pending', 'total' => $calculated_total];

    } catch (Exception $e) {
        $db->rollback();
        throw $e;
    }
}

function createOrder($db, $data) {
    // Legacy function - redirect to new transactional version
    return createOrderWithTransaction($db, $data);
}

function routeToPaymentProvider($payment_method, $transaction_id, $data) {
    switch ($payment_method) {
        case 'stc_pay':
            return initializeSTCPay($transaction_id, $data);

        case 'tamara':
            return initializeTamara($transaction_id, $data);

        case 'tabby':
            return initializeTabby($transaction_id, $data);

        case 'google_pay':
            return initializeGooglePay($transaction_id, $data);

        case 'apple_pay':
            return initializeApplePay($transaction_id, $data);

        case 'bank_transfer':
            return initializeBankTransfer($transaction_id, $data);

        case 'visa':
        case 'mastercard':
        case 'mada':
        case 'amex':
        case 'unionpay':
            return initializeCardPayment($payment_method, $transaction_id, $data);

        case 'paypal':
            return initializePayPal($transaction_id, $data);

        case 'sadad':
            return initializeSadad($transaction_id, $data);

        case 'fawry':
            return initializeFawry($transaction_id, $data);

        case 'urpay':
            return initializeUrPay($transaction_id, $data);

        case 'benefit':
            return initializeBenefit($transaction_id, $data);

        default:
            throw new Exception('Unsupported payment method: ' . $payment_method);
    }
}

function initializeSTCPay($transaction_id, $data) {
    // TODO: Integrate with STC Pay API
    // This is a placeholder - replace with actual STC Pay integration
    return [
        'status' => 'redirect',
        'redirect_url' => 'https://stcpay.com.sa/payment?ref=' . $transaction_id,
        'transaction_id' => $transaction_id,
        'provider' => 'stc_pay',
        'message' => 'Redirecting to STC Pay'
    ];
}

function initializeTamara($transaction_id, $data) {
    // TODO: Integrate with Tamara API
    // This is a placeholder - replace with actual Tamara integration
    return [
        'status' => 'redirect',
        'redirect_url' => 'https://api.tamara.co/checkout?ref=' . $transaction_id,
        'transaction_id' => $transaction_id,
        'provider' => 'tamara',
        'installments' => $data['installments'] ?? 3,
        'message' => 'Redirecting to Tamara'
    ];
}

function initializeTabby($transaction_id, $data) {
    // TODO: Integrate with Tabby API
    // This is a placeholder - replace with actual Tabby integration
    return [
        'status' => 'redirect',
        'redirect_url' => 'https://api.tabby.ai/checkout?ref=' . $transaction_id,
        'transaction_id' => $transaction_id,
        'provider' => 'tabby',
        'installments' => 4,
        'message' => 'Redirecting to Tabby'
    ];
}

function initializeGooglePay($transaction_id, $data) {
    // TODO: Integrate with Google Pay API
    // This is a placeholder - replace with actual Google Pay integration
    return [
        'status' => 'redirect',
        'redirect_url' => '/payment/google-pay?transaction=' . $transaction_id,
        'transaction_id' => $transaction_id,
        'provider' => 'google_pay',
        'message' => 'Initializing Google Pay'
    ];
}

function initializeApplePay($transaction_id, $data) {
    // TODO: Integrate with Apple Pay API
    // This is a placeholder - replace with actual Apple Pay integration
    return [
        'status' => 'redirect',
        'redirect_url' => '/payment/apple-pay?transaction=' . $transaction_id,
        'transaction_id' => $transaction_id,
        'provider' => 'apple_pay',
        'message' => 'Initializing Apple Pay'
    ];
}

function initializeBankTransfer($transaction_id, $data) {
    // For bank transfer, we provide bank details immediately
    return [
        'status' => 'pending',
        'transaction_id' => $transaction_id,
        'provider' => 'bank_transfer',
        'bank_details' => [
            'bank_name' => 'البنك الأهلي السعودي',
            'account_number' => '1234567890',
            'iban' => 'SA0510000012345678901',
            'account_holder' => 'دار زيد للنشر والتوزيع',
            'swift_code' => 'NCBKSARIXX'
        ],
        'reference_number' => $transaction_id,
        'amount' => $data['amount'],
        'currency' => $data['currency'] ?? 'SAR',
        'message' => 'Please transfer the amount using the provided bank details'
    ];
}

function initializeCardPayment($payment_method, $transaction_id, $data) {
    // TODO: Integrate with card payment processor (e.g., Moyasar, PayTabs, HyperPay)
    // This is a placeholder - replace with actual card payment integration
    return [
        'status' => 'redirect',
        'redirect_url' => '/payment/card-form?method=' . $payment_method . '&transaction=' . $transaction_id,
        'transaction_id' => $transaction_id,
        'provider' => $payment_method,
        'message' => 'Redirecting to secure card payment form'
    ];
}

function initializePayPal($transaction_id, $data) {
    // TODO: Integrate with PayPal API
    // This is a placeholder - replace with actual PayPal integration
    return [
        'status' => 'redirect',
        'redirect_url' => 'https://www.paypal.com/checkout?ref=' . $transaction_id,
        'transaction_id' => $transaction_id,
        'provider' => 'paypal',
        'message' => 'Redirecting to PayPal'
    ];
}

function initializeSadad($transaction_id, $data) {
    // TODO: Integrate with Sadad API
    // This is a placeholder - replace with actual Sadad integration
    return [
        'status' => 'redirect',
        'redirect_url' => 'https://sadad.com.sa/payment?ref=' . $transaction_id,
        'transaction_id' => $transaction_id,
        'provider' => 'sadad',
        'message' => 'Redirecting to Sadad'
    ];
}

function initializeFawry($transaction_id, $data) {
    // TODO: Integrate with Fawry API
    // This is a placeholder - replace with actual Fawry integration
    return [
        'status' => 'redirect',
        'redirect_url' => 'https://fawry.com/payment?ref=' . $transaction_id,
        'transaction_id' => $transaction_id,
        'provider' => 'fawry',
        'message' => 'Redirecting to Fawry'
    ];
}

function initializeUrPay($transaction_id, $data) {
    // TODO: Integrate with UrPay API
    // This is a placeholder - replace with actual UrPay integration
    return [
        'status' => 'redirect',
        'redirect_url' => 'https://urpay.com.sa/payment?ref=' . $transaction_id,
        'transaction_id' => $transaction_id,
        'provider' => 'urpay',
        'message' => 'Redirecting to UrPay'
    ];
}

function initializeBenefit($transaction_id, $data) {
    // TODO: Integrate with Benefit API
    // This is a placeholder - replace with actual Benefit integration
    return [
        'status' => 'redirect',
        'redirect_url' => 'https://benefit.com.sa/payment?ref=' . $transaction_id,
        'transaction_id' => $transaction_id,
        'provider' => 'benefit',
        'message' => 'Redirecting to Benefit'
    ];
}

function handlePaymentProcessing($db) {
    // This would handle the actual payment processing
    // For now, it's a placeholder
    echo json_encode(['message' => 'Payment processing endpoint - to be implemented'], JSON_UNESCAPED_UNICODE);
}

function handlePaymentCallback($db) {
    try {
        $raw_payload = file_get_contents('php://input');
        $data = json_decode($raw_payload, true);
        $headers = getallheaders();

        // Verify webhook authenticity (implement per provider)
        $webhook_signature = $headers['X-Webhook-Signature'] ?? $headers['x-webhook-signature'] ?? '';
        if (!verifyWebhookSignature($raw_payload, $webhook_signature)) {
            http_response_code(401);
            echo json_encode(['error' => 'Invalid webhook signature'], JSON_UNESCAPED_UNICODE);
            return;
        }

        $transaction_id = $data['transaction_id'] ?? '';
        $status = $data['status'] ?? 'failed';
        $provider_transaction_id = $data['provider_transaction_id'] ?? '';
        $provider_response = $data;

        if (!$transaction_id) {
            http_response_code(400);
            echo json_encode(['error' => 'Transaction ID required'], JSON_UNESCAPED_UNICODE);
            return;
        }

        // Start transaction for atomic payment update
        $db->beginTransaction();

        try {
            // Check for idempotent processing (avoid duplicate webhook processing)
            $checkStmt = $db->prepare(
                'SELECT id, status FROM payments WHERE transaction_id = :transaction_id FOR UPDATE'
            );
            $checkStmt->execute(['transaction_id' => $transaction_id]);
            $payment = $checkStmt->fetch(PDO::FETCH_ASSOC);

            if (!$payment) {
                $db->rollback();
                http_response_code(404);
                echo json_encode(['error' => 'Payment not found'], JSON_UNESCAPED_UNICODE);
                return;
            }

            // Prevent duplicate processing
            if ($payment['status'] === 'completed' && $status === 'completed') {
                $db->commit();
                echo json_encode(['status' => 'success', 'message' => 'Already processed'], JSON_UNESCAPED_UNICODE);
                return;
            }

            // Update payment status
            $stmt = $db->prepare(
                'UPDATE payments SET status = :status, provider_transaction_id = :provider_id,
                                   provider_response = :provider_response, webhook_verified = TRUE,
                                   webhook_signature = :signature, updated_at = CURRENT_TIMESTAMP
                 WHERE transaction_id = :transaction_id'
            );

            $stmt->execute([
                'status' => $status,
                'provider_id' => $provider_transaction_id,
                'provider_response' => json_encode($provider_response),
                'signature' => $webhook_signature,
                'transaction_id' => $transaction_id
            ]);

            // Handle different payment statuses
            if ($status === 'completed') {
                handlePaymentSuccess($db, $transaction_id);
            } elseif ($status === 'failed' || $status === 'cancelled') {
                handlePaymentFailure($db, $transaction_id);
            }

            $db->commit();
            echo json_encode(['status' => 'success', 'message' => 'Callback processed'], JSON_UNESCAPED_UNICODE);

        } catch (Exception $e) {
            $db->rollback();
            throw $e;
        }

    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Callback processing error: ' . $e->getMessage()], JSON_UNESCAPED_UNICODE);
    }
}

function verifyWebhookSignature($payload, $signature) {
    // TODO: Implement signature verification per payment provider
    // Each provider has different signature schemes
    // For now, return true - implement actual verification in production
    return true;
}

function handlePaymentSuccess($db, $transaction_id) {
    // Update order status to paid
    $orderStmt = $db->prepare(
        'UPDATE orders SET payment_status = "paid", status = "paid"
         WHERE order_id = (SELECT order_id FROM payments WHERE transaction_id = :transaction_id)'
    );
    $orderStmt->execute(['transaction_id' => $transaction_id]);

    // Stock was already reserved during order creation, so no further stock changes needed
    // In future: could implement stock confirmation here if using reservation system
}

function handlePaymentFailure($db, $transaction_id) {
    // Get order details to restore stock
    $orderStmt = $db->prepare(
        'SELECT o.order_id, o.items FROM orders o
         JOIN payments p ON o.order_id = p.order_id
         WHERE p.transaction_id = :transaction_id'
    );
    $orderStmt->execute(['transaction_id' => $transaction_id]);
    $order = $orderStmt->fetch(PDO::FETCH_ASSOC);

    if ($order) {
        // Restore stock for failed payment
        $items = json_decode($order['items'], true);
        foreach ($items as $item) {
            $restoreStmt = $db->prepare(
                'UPDATE books SET stock_quantity = stock_quantity + :quantity WHERE id = :id'
            );
            $restoreStmt->execute([
                'quantity' => $item['quantity'],
                'id' => $item['id']
            ]);
        }

        // Update order status
        $updateOrderStmt = $db->prepare(
            'UPDATE orders SET payment_status = "failed", status = "cancelled", stock_reserved = FALSE
             WHERE order_id = :order_id'
        );
        $updateOrderStmt->execute(['order_id' => $order['order_id']]);
    }
}

function handlePaymentVerification($db, $transaction_id) {
    try {
        if (!$transaction_id) {
            http_response_code(400);
            echo json_encode(['error' => 'Transaction ID is required'], JSON_UNESCAPED_UNICODE);
            return;
        }

        $stmt = $db->prepare(
            'SELECT p.*, o.order_id, o.total_amount as order_total
             FROM payments p
             LEFT JOIN orders o ON p.order_id = o.order_id
             WHERE p.transaction_id = :transaction_id'
        );

        $stmt->execute(['transaction_id' => $transaction_id]);
        $payment = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$payment) {
            http_response_code(404);
            echo json_encode(['error' => 'Payment not found'], JSON_UNESCAPED_UNICODE);
            return;
        }

        echo json_encode([
            'transaction_id' => $payment['transaction_id'],
            'status' => $payment['status'],
            'amount' => (float)$payment['amount'],
            'currency' => $payment['currency'],
            'payment_method' => $payment['payment_method'],
            'created_at' => $payment['created_at'],
            'updated_at' => $payment['updated_at']
        ], JSON_UNESCAPED_UNICODE);

    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Verification error: ' . $e->getMessage()], JSON_UNESCAPED_UNICODE);
    }
}

function handlePaymentHistory($db) {
    try {
        $user_id = $_GET['user_id'] ?? null;
        $limit = (int)($_GET['limit'] ?? 10);
        $offset = (int)($_GET['offset'] ?? 0);

        $whereClause = '';
        $params = [];

        if ($user_id) {
            $whereClause = 'WHERE JSON_EXTRACT(customer_info, "$.user_id") = :user_id';
            $params['user_id'] = $user_id;
        }

        $stmt = $db->prepare(
            "SELECT transaction_id, order_id, payment_method, amount, currency, status, created_at
             FROM payments $whereClause
             ORDER BY created_at DESC
             LIMIT :limit OFFSET :offset"
        );

        foreach ($params as $key => $value) {
            $stmt->bindValue(':' . $key, $value);
        }
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);

        $stmt->execute();
        $payments = $stmt->fetchAll(PDO::FETCH_ASSOC);

        echo json_encode(['payments' => $payments], JSON_UNESCAPED_UNICODE);

    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['error' => 'History retrieval error: ' . $e->getMessage()], JSON_UNESCAPED_UNICODE);
    }
}

function handlePaymentRefund($db) {
    try {
        $data = json_decode(file_get_contents('php://input'), true);

        $transaction_id = $data['transaction_id'] ?? '';
        $amount = (float)($data['amount'] ?? 0);
        $reason = $data['reason'] ?? '';

        if (!$transaction_id || $amount <= 0) {
            http_response_code(400);
            echo json_encode(['error' => 'Transaction ID and amount are required'], JSON_UNESCAPED_UNICODE);
            return;
        }

        // TODO: Implement actual refund processing with payment providers
        // This is a placeholder

        $stmt = $db->prepare(
            'UPDATE payments SET status = "refunded", updated_at = CURRENT_TIMESTAMP
             WHERE transaction_id = :transaction_id AND status = "completed"'
        );

        $stmt->execute(['transaction_id' => $transaction_id]);

        if ($stmt->rowCount() > 0) {
            echo json_encode([
                'status' => 'success',
                'message' => 'Refund initiated',
                'refund_amount' => $amount,
                'transaction_id' => $transaction_id
            ], JSON_UNESCAPED_UNICODE);
        } else {
            http_response_code(404);
            echo json_encode(['error' => 'Payment not found or cannot be refunded'], JSON_UNESCAPED_UNICODE);
        }

    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Refund error: ' . $e->getMessage()], JSON_UNESCAPED_UNICODE);
    }
}
?>