<?php
require_once '../config/database.php';

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

// Create payments table if not exists
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
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_transaction_id (transaction_id),
    INDEX idx_order_id (order_id),
    INDEX idx_status (status),
    INDEX idx_payment_method (payment_method)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

// Create orders table if not exists
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
    status ENUM('pending', 'confirmed', 'processing', 'shipped', 'delivered', 'cancelled') DEFAULT 'pending',
    payment_status ENUM('pending', 'paid', 'failed', 'refunded') DEFAULT 'pending',
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_order_id (order_id),
    INDEX idx_customer_id (customer_id),
    INDEX idx_status (status),
    INDEX idx_payment_status (payment_status)
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

        $payment_method = $data['payment_method'];
        $amount = (float)$data['amount'];
        $currency = $data['currency'] ?? 'SAR';
        $order_id = $data['order_id'] ?? 'order_' . time();
        $transaction_id = 'txn_' . time() . '_' . uniqid();

        // Create order if it doesn't exist
        createOrder($db, $data);

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

function createOrder($db, $data) {
    $order_id = $data['order_id'] ?? 'order_' . time();

    // Check if order already exists
    $stmt = $db->prepare('SELECT id FROM orders WHERE order_id = :order_id');
    $stmt->execute(['order_id' => $order_id]);

    if ($stmt->fetch()) {
        return; // Order already exists
    }

    $subtotal = 0;
    if (isset($data['items']) && is_array($data['items'])) {
        foreach ($data['items'] as $item) {
            $subtotal += (float)($item['price'] ?? 0) * (int)($item['quantity'] ?? 1);
        }
    }

    $shipping_cost = (float)($data['shipping_cost'] ?? 0);
    $tax_amount = (float)($data['tax_amount'] ?? 0);
    $total_amount = $subtotal + $shipping_cost + $tax_amount;

    $stmt = $db->prepare(
        'INSERT INTO orders (order_id, customer_info, items, shipping_address, billing_address,
                           subtotal, shipping_cost, tax_amount, total_amount, currency)
         VALUES (:order_id, :customer_info, :items, :shipping_address, :billing_address,
                 :subtotal, :shipping_cost, :tax_amount, :total_amount, :currency)'
    );

    $stmt->execute([
        'order_id' => $order_id,
        'customer_info' => json_encode($data['customer_info'] ?? []),
        'items' => json_encode($data['items'] ?? []),
        'shipping_address' => json_encode($data['shipping_address'] ?? []),
        'billing_address' => json_encode($data['billing_address'] ?? []),
        'subtotal' => $subtotal,
        'shipping_cost' => $shipping_cost,
        'tax_amount' => $tax_amount,
        'total_amount' => $total_amount,
        'currency' => $data['currency'] ?? 'SAR'
    ]);
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
            return initializeCardPayment($payment_method, $transaction_id, $data);

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

function handlePaymentProcessing($db) {
    // This would handle the actual payment processing
    // For now, it's a placeholder
    echo json_encode(['message' => 'Payment processing endpoint - to be implemented'], JSON_UNESCAPED_UNICODE);
}

function handlePaymentCallback($db) {
    try {
        $data = json_decode(file_get_contents('php://input'), true);

        // Process callback from payment providers
        // This is where you'd verify the payment status with the provider
        // and update your database accordingly

        $transaction_id = $data['transaction_id'] ?? '';
        $status = $data['status'] ?? 'failed';
        $provider_transaction_id = $data['provider_transaction_id'] ?? '';
        $provider_response = $data;

        if ($transaction_id) {
            $stmt = $db->prepare(
                'UPDATE payments SET status = :status, provider_transaction_id = :provider_id,
                                   provider_response = :provider_response, updated_at = CURRENT_TIMESTAMP
                 WHERE transaction_id = :transaction_id'
            );

            $stmt->execute([
                'status' => $status,
                'provider_id' => $provider_transaction_id,
                'provider_response' => json_encode($provider_response),
                'transaction_id' => $transaction_id
            ]);

            // Update order status if payment is completed
            if ($status === 'completed') {
                $orderStmt = $db->prepare(
                    'UPDATE orders SET payment_status = "paid", status = "confirmed"
                     WHERE order_id = (SELECT order_id FROM payments WHERE transaction_id = :transaction_id)'
                );
                $orderStmt->execute(['transaction_id' => $transaction_id]);
            }
        }

        echo json_encode(['status' => 'success', 'message' => 'Callback processed'], JSON_UNESCAPED_UNICODE);

    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Callback processing error: ' . $e->getMessage()], JSON_UNESCAPED_UNICODE);
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