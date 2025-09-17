<?php
require_once '../config/database.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: ' . (isset($_SERVER['HTTP_ORIGIN']) ? $_SERVER['HTTP_ORIGIN'] : 'http://localhost:3000'));
header('Vary: Origin');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');
header('Access-Control-Allow-Credentials: true');

$method = $_SERVER['REQUEST_METHOD'];
if ($method === 'OPTIONS') {
    http_response_code(200);
    exit();
}

$database = new Database();
$db = $database->getConnection();

if ($method === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    $customerInfo = $data['customerInfo'] ?? null;
    $items = $data['items'] ?? null;
    $paymentMethod = $data['paymentMethod'] ?? null;
    $total = isset($data['total']) ? floatval($data['total']) : null;

    if (!$customerInfo || !$items || !$paymentMethod || $total === null) {
        http_response_code(400);
        echo json_encode(['error' => 'Missing required fields']);
        exit();
    }

    $orderId = 'ORD-' . time();

    try {
        $db->beginTransaction();

        // Create minimal order (assuming users table optional)
        $stmt = $db->prepare("INSERT INTO orders (user_id, status) VALUES (:user_id, :status)");
        $userId = $customerInfo['user_id'] ?? 0;
        $stmt->execute([':user_id' => $userId, ':status' => 'pending']);
        $newOrderId = $db->lastInsertId();

        // Insert order items
        $oi = $db->prepare("INSERT INTO order_items (order_id, book_id, quantity, price) VALUES (:order_id, :book_id, :quantity, :price)");
        foreach ($items as $it) {
            $oi->execute([
                ':order_id' => $newOrderId,
                ':book_id' => intval($it['id']),
                ':quantity' => intval($it['quantity']),
                ':price' => floatval($it['price'])
            ]);
        }

        $db->commit();

        // Initiate payment
        $provider = $paymentMethod;
        if ($provider === 'moyasar' || $provider === 'visa') {
            $apiKey = getenv('MOYASAR_API_KEY');
            if (!$apiKey) {
                echo json_encode([
                    'success' => false,
                    'error' => 'Payment not configured (MOYASAR_API_KEY)'
                ]);
                exit();
            }

            $payload = [
                'amount' => intval($total * 100),
                'currency' => 'SAR',
                'description' => 'طلب رقم ' . $orderId,
                'source' => [ 'type' => 'creditcard' ],
                'callback_url' => (isset($_SERVER['HTTP_ORIGIN']) ? $_SERVER['HTTP_ORIGIN'] : '') . '/backend/api/orders_callback.php?provider=moyasar',
                'metadata' => [
                    'orderId' => $newOrderId,
                    'customerName' => $customerInfo['name'] ?? '',
                    'customerEmail' => $customerInfo['email'] ?? ''
                ]
            ];

            $ch = curl_init('https://api.moyasar.com/v1/payments');
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, [ 'Content-Type: application/json' ]);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload, JSON_UNESCAPED_UNICODE));
            curl_setopt($ch, CURLOPT_USERPWD, $apiKey . ':');
            $resp = curl_exec($ch);
            $err = curl_error($ch);
            $statusCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            if ($err) {
                echo json_encode(['success' => false, 'error' => 'Payment request failed']);
                exit();
            }

            $body = json_decode($resp, true);
            // Moyasar returns a payment object; if 3DS is required, it includes next_action and source.transaction_url
            $redirectUrl = null;
            if (isset($body['source']) && isset($body['source']['transaction_url'])) {
                $redirectUrl = $body['source']['transaction_url'];
            } elseif (isset($body['next_action']) && isset($body['next_action']['redirect_to_url'])) {
                $redirectUrl = $body['next_action']['redirect_to_url'];
            }

            echo json_encode([
                'success' => $statusCode >= 200 && $statusCode < 300,
                'order' => [ 'id' => $newOrderId, 'status' => 'pending', 'total' => $total ],
                'paymentResult' => $body,
                'redirectUrl' => $redirectUrl
            ], JSON_UNESCAPED_UNICODE);
            exit();
        } elseif ($provider === 'stc_pay') {
            $apiKey = getenv('STC_PAY_API_KEY');
            $baseUrl = getenv('STC_PAY_BASE_URL') ?: 'https://api.stcpay.com.sa';
            if (!$apiKey) {
                echo json_encode([ 'success' => false, 'error' => 'Payment not configured (STC_PAY_API_KEY)' ]);
                exit();
            }
            $payload = [
                'amount' => intval($total * 100),
                'currency' => 'SAR',
                'orderId' => $orderId,
                'customerReference' => $customerInfo['phone'] ?? '',
                'description' => 'طلب رقم ' . $orderId,
                'callbackUrl' => (isset($_SERVER['HTTP_ORIGIN']) ? $_SERVER['HTTP_ORIGIN'] : '') . '/backend/api/orders_callback.php?provider=stc_pay',
                'redirectUrl' => (isset($_SERVER['HTTP_ORIGIN']) ? $_SERVER['HTTP_ORIGIN'] : '') . '/payment/success'
            ];
            $ch = curl_init(rtrim($baseUrl, '/') . '/v2/directPayment');
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Authorization: Bearer ' . $apiKey,
                'Content-Type: application/json'
            ]);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload, JSON_UNESCAPED_UNICODE));
            $resp = curl_exec($ch);
            $err = curl_error($ch);
            $statusCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
            if ($err) {
                echo json_encode(['success' => false, 'error' => 'Payment request failed']);
                exit();
            }
            $body = json_decode($resp, true);
            $redirectUrl = $body['redirectUrl'] ?? ($body['paymentUrl'] ?? null);
            echo json_encode([
                'success' => $statusCode >= 200 && $statusCode < 300,
                'order' => [ 'id' => $newOrderId, 'status' => 'pending', 'total' => $total ],
                'paymentResult' => $body,
                'redirectUrl' => $redirectUrl
            ], JSON_UNESCAPED_UNICODE);
            exit();
        }

        echo json_encode([
            'success' => true,
            'order' => [ 'id' => $newOrderId, 'status' => 'pending', 'total' => $total ],
        ]);
        exit();
    } catch (Exception $e) {
        if ($db->inTransaction()) $db->rollBack();
        http_response_code(500);
        echo json_encode(['error' => 'Server error: ' . $e->getMessage()]);
        exit();
    }
} elseif ($method === 'GET') {
    // Basic list for admin (no auth here for brevity)
    $status = isset($_GET['status']) ? $_GET['status'] : null;
    $sql = 'SELECT o.*, (SELECT SUM(oi.quantity * oi.price) FROM order_items oi WHERE oi.order_id = o.id) AS total_amount FROM orders o';
    $params = [];
    if ($status) { $sql .= ' WHERE o.status = :status'; $params[':status'] = $status; }
    $sql .= ' ORDER BY o.created_at DESC';
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo json_encode(['orders' => $orders]);
    exit();
} else {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
}

?>


