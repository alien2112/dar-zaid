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

// Create subscriptions table with proper state tracking
$db->exec("CREATE TABLE IF NOT EXISTS subscriptions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    subscription_id VARCHAR(255) UNIQUE NOT NULL,
    customer_id INT NOT NULL,
    package_id INT NOT NULL,
    status ENUM('active', 'expired', 'cancelled', 'pending_renewal', 'suspended') DEFAULT 'pending_renewal',
    start_date DATE NOT NULL,
    end_date DATE NOT NULL,
    next_billing_date DATE,
    auto_renew BOOLEAN DEFAULT TRUE,
    payment_method VARCHAR(50),
    amount DECIMAL(10, 2) NOT NULL,
    currency VARCHAR(3) DEFAULT 'SAR',
    renewal_attempts INT DEFAULT 0,
    last_renewal_attempt TIMESTAMP NULL,
    renewal_failure_reason TEXT,
    metadata JSON,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_subscription_id (subscription_id),
    INDEX idx_customer_id (customer_id),
    INDEX idx_package_id (package_id),
    INDEX idx_status (status),
    INDEX idx_next_billing_date (next_billing_date),
    INDEX idx_auto_renew (auto_renew),
    FOREIGN KEY (package_id) REFERENCES publishing_packages(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

// Create subscription payments tracking table
$db->exec("CREATE TABLE IF NOT EXISTS subscription_payments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    subscription_id VARCHAR(255) NOT NULL,
    payment_transaction_id VARCHAR(255) NOT NULL,
    payment_period_start DATE NOT NULL,
    payment_period_end DATE NOT NULL,
    amount DECIMAL(10, 2) NOT NULL,
    currency VARCHAR(3) DEFAULT 'SAR',
    status ENUM('pending', 'completed', 'failed', 'refunded') DEFAULT 'pending',
    payment_method VARCHAR(50),
    idempotency_key VARCHAR(255) UNIQUE,
    provider_transaction_id VARCHAR(255),
    failure_reason TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_subscription_id (subscription_id),
    INDEX idx_payment_transaction_id (payment_transaction_id),
    INDEX idx_status (status),
    INDEX idx_payment_period (payment_period_start, payment_period_end),
    INDEX idx_idempotency_key (idempotency_key),
    FOREIGN KEY (subscription_id) REFERENCES subscriptions(subscription_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

$request_uri = $_SERVER['REQUEST_URI'];
$path_parts = explode('/', trim(parse_url($request_uri, PHP_URL_PATH), '/'));
$endpoint = $path_parts[count($path_parts) - 1] ?? '';

switch ($method) {
    case 'POST':
        if ($endpoint === 'create') {
            handleSubscriptionCreation($db);
        } elseif ($endpoint === 'renew') {
            handleSubscriptionRenewal($db);
        } elseif ($endpoint === 'cancel') {
            handleSubscriptionCancellation($db);
        } elseif ($endpoint === 'process-renewals') {
            processAutomaticRenewals($db);
        } else {
            http_response_code(404);
            echo json_encode(['error' => 'Endpoint not found'], JSON_UNESCAPED_UNICODE);
        }
        break;

    case 'GET':
        if ($endpoint === 'list') {
            handleSubscriptionList($db);
        } elseif (strpos($endpoint, 'status') === 0) {
            $subscription_id = $_GET['subscription_id'] ?? '';
            handleSubscriptionStatus($db, $subscription_id);
        } else {
            http_response_code(404);
            echo json_encode(['error' => 'Endpoint not found'], JSON_UNESCAPED_UNICODE);
        }
        break;

    case 'PUT':
        if ($endpoint === 'update') {
            handleSubscriptionUpdate($db);
        } else {
            http_response_code(404);
            echo json_encode(['error' => 'Endpoint not found'], JSON_UNESCAPED_UNICODE);
        }
        break;

    default:
        http_response_code(405);
        echo json_encode(['error' => 'Method not allowed'], JSON_UNESCAPED_UNICODE);
}

function handleSubscriptionCreation($db) {
    try {
        $data = json_decode(file_get_contents('php://input'), true);

        if (!$data || !isset($data['customer_id']) || !isset($data['package_id'])) {
            http_response_code(400);
            echo json_encode(['error' => 'Customer ID and package ID are required'], JSON_UNESCAPED_UNICODE);
            return;
        }

        $customer_id = (int)$data['customer_id'];
        $package_id = (int)$data['package_id'];
        $subscription_id = 'sub_' . time() . '_' . uniqid();

        // Get package details
        $packageStmt = $db->prepare('SELECT * FROM publishing_packages WHERE id = :id AND is_active = 1');
        $packageStmt->execute(['id' => $package_id]);
        $package = $packageStmt->fetch(PDO::FETCH_ASSOC);

        if (!$package) {
            http_response_code(404);
            echo json_encode(['error' => 'Package not found or inactive'], JSON_UNESCAPED_UNICODE);
            return;
        }

        $start_date = date('Y-m-d');
        $end_date = date('Y-m-d', strtotime('+1 year')); // 1 year subscription
        $next_billing_date = $end_date;

        $stmt = $db->prepare(
            'INSERT INTO subscriptions (subscription_id, customer_id, package_id, status, start_date, end_date,
                                     next_billing_date, auto_renew, payment_method, amount, currency, metadata)
             VALUES (:subscription_id, :customer_id, :package_id, :status, :start_date, :end_date,
                     :next_billing_date, :auto_renew, :payment_method, :amount, :currency, :metadata)'
        );

        $stmt->execute([
            'subscription_id' => $subscription_id,
            'customer_id' => $customer_id,
            'package_id' => $package_id,
            'status' => 'active',
            'start_date' => $start_date,
            'end_date' => $end_date,
            'next_billing_date' => $next_billing_date,
            'auto_renew' => (bool)($data['auto_renew'] ?? true),
            'payment_method' => $data['payment_method'] ?? null,
            'amount' => (float)$package['price'],
            'currency' => $package['currency'] ?? 'SAR',
            'metadata' => json_encode($data['metadata'] ?? [])
        ]);

        echo json_encode([
            'subscription_id' => $subscription_id,
            'status' => 'active',
            'start_date' => $start_date,
            'end_date' => $end_date,
            'amount' => (float)$package['price'],
            'message' => 'Subscription created successfully'
        ], JSON_UNESCAPED_UNICODE);

    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Subscription creation error: ' . $e->getMessage()], JSON_UNESCAPED_UNICODE);
    }
}

function handleSubscriptionRenewal($db) {
    try {
        $data = json_decode(file_get_contents('php://input'), true);
        $subscription_id = $data['subscription_id'] ?? '';
        $idempotency_key = $data['idempotency_key'] ?? 'renewal_' . time() . '_' . uniqid();

        if (!$subscription_id) {
            http_response_code(400);
            echo json_encode(['error' => 'Subscription ID is required'], JSON_UNESCAPED_UNICODE);
            return;
        }

        $db->beginTransaction();

        try {
            // Check for idempotent renewal
            $checkStmt = $db->prepare(
                'SELECT id FROM subscription_payments WHERE idempotency_key = :key'
            );
            $checkStmt->execute(['key' => $idempotency_key]);
            if ($checkStmt->fetch()) {
                $db->commit();
                echo json_encode(['status' => 'success', 'message' => 'Renewal already processed'], JSON_UNESCAPED_UNICODE);
                return;
            }

            // Get subscription details with lock
            $subStmt = $db->prepare(
                'SELECT * FROM subscriptions WHERE subscription_id = :id FOR UPDATE'
            );
            $subStmt->execute(['id' => $subscription_id]);
            $subscription = $subStmt->fetch(PDO::FETCH_ASSOC);

            if (!$subscription) {
                $db->rollback();
                http_response_code(404);
                echo json_encode(['error' => 'Subscription not found'], JSON_UNESCAPED_UNICODE);
                return;
            }

            if ($subscription['status'] === 'cancelled') {
                $db->rollback();
                http_response_code(400);
                echo json_encode(['error' => 'Cannot renew cancelled subscription'], JSON_UNESCAPED_UNICODE);
                return;
            }

            // Calculate new billing period
            $current_end = $subscription['end_date'];
            $new_end_date = date('Y-m-d', strtotime($current_end . ' +1 year'));
            $new_billing_date = $new_end_date;

            // Create payment record for renewal
            $payment_transaction_id = 'renewal_txn_' . time() . '_' . uniqid();

            $paymentStmt = $db->prepare(
                'INSERT INTO subscription_payments (subscription_id, payment_transaction_id, payment_period_start,
                                                  payment_period_end, amount, currency, status, payment_method, idempotency_key)
                 VALUES (:subscription_id, :payment_transaction_id, :period_start, :period_end,
                         :amount, :currency, :status, :payment_method, :idempotency_key)'
            );

            $paymentStmt->execute([
                'subscription_id' => $subscription_id,
                'payment_transaction_id' => $payment_transaction_id,
                'period_start' => $current_end,
                'period_end' => $new_end_date,
                'amount' => $subscription['amount'],
                'currency' => $subscription['currency'],
                'status' => 'pending',
                'payment_method' => $subscription['payment_method'],
                'idempotency_key' => $idempotency_key
            ]);

            // Update subscription
            $updateStmt = $db->prepare(
                'UPDATE subscriptions SET end_date = :end_date, next_billing_date = :billing_date,
                                        status = :status, renewal_attempts = 0, last_renewal_attempt = CURRENT_TIMESTAMP
                 WHERE subscription_id = :subscription_id'
            );

            $updateStmt->execute([
                'end_date' => $new_end_date,
                'billing_date' => $new_billing_date,
                'status' => 'active',
                'subscription_id' => $subscription_id
            ]);

            $db->commit();

            echo json_encode([
                'status' => 'success',
                'subscription_id' => $subscription_id,
                'payment_transaction_id' => $payment_transaction_id,
                'new_end_date' => $new_end_date,
                'amount' => $subscription['amount'],
                'message' => 'Subscription renewed successfully'
            ], JSON_UNESCAPED_UNICODE);

        } catch (Exception $e) {
            $db->rollback();
            throw $e;
        }

    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Renewal error: ' . $e->getMessage()], JSON_UNESCAPED_UNICODE);
    }
}

function processAutomaticRenewals($db) {
    try {
        // Find subscriptions that need renewal (ending in next 7 days)
        $stmt = $db->prepare(
            'SELECT * FROM subscriptions
             WHERE auto_renew = TRUE
             AND status = "active"
             AND end_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 7 DAY)
             AND renewal_attempts < 3'
        );
        $stmt->execute();
        $subscriptions = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $processed = 0;
        $failed = 0;

        foreach ($subscriptions as $subscription) {
            try {
                // Attempt automatic renewal
                $result = attemptAutomaticRenewal($db, $subscription);
                if ($result) {
                    $processed++;
                } else {
                    $failed++;
                }
            } catch (Exception $e) {
                $failed++;
                error_log('Auto-renewal failed for subscription ' . $subscription['subscription_id'] . ': ' . $e->getMessage());
            }
        }

        echo json_encode([
            'status' => 'completed',
            'processed' => $processed,
            'failed' => $failed,
            'total_checked' => count($subscriptions)
        ], JSON_UNESCAPED_UNICODE);

    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Auto-renewal processing error: ' . $e->getMessage()], JSON_UNESCAPED_UNICODE);
    }
}

function attemptAutomaticRenewal($db, $subscription) {
    $db->beginTransaction();

    try {
        // Increment renewal attempts
        $updateStmt = $db->prepare(
            'UPDATE subscriptions SET renewal_attempts = renewal_attempts + 1,
                                    last_renewal_attempt = CURRENT_TIMESTAMP
             WHERE subscription_id = :id'
        );
        $updateStmt->execute(['id' => $subscription['subscription_id']]);

        // TODO: Integrate with payment provider for automatic charging
        // For now, simulate the renewal process

        // If payment succeeds, extend subscription
        $new_end_date = date('Y-m-d', strtotime($subscription['end_date'] . ' +1 year'));

        $renewStmt = $db->prepare(
            'UPDATE subscriptions SET end_date = :end_date, next_billing_date = :billing_date,
                                    renewal_attempts = 0, status = :status
             WHERE subscription_id = :id'
        );

        $renewStmt->execute([
            'end_date' => $new_end_date,
            'billing_date' => $new_end_date,
            'status' => 'active',
            'id' => $subscription['subscription_id']
        ]);

        $db->commit();
        return true;

    } catch (Exception $e) {
        $db->rollback();

        // Mark as pending renewal if max attempts reached
        if ($subscription['renewal_attempts'] >= 2) {
            $failStmt = $db->prepare(
                'UPDATE subscriptions SET status = "pending_renewal",
                                        renewal_failure_reason = :reason
                 WHERE subscription_id = :id'
            );
            $failStmt->execute([
                'reason' => $e->getMessage(),
                'id' => $subscription['subscription_id']
            ]);
        }

        return false;
    }
}

function handleSubscriptionCancellation($db) {
    try {
        $data = json_decode(file_get_contents('php://input'), true);
        $subscription_id = $data['subscription_id'] ?? '';

        if (!$subscription_id) {
            http_response_code(400);
            echo json_encode(['error' => 'Subscription ID is required'], JSON_UNESCAPED_UNICODE);
            return;
        }

        $stmt = $db->prepare(
            'UPDATE subscriptions SET status = "cancelled", auto_renew = FALSE, updated_at = CURRENT_TIMESTAMP
             WHERE subscription_id = :id AND status != "cancelled"'
        );
        $stmt->execute(['id' => $subscription_id]);

        if ($stmt->rowCount() > 0) {
            echo json_encode([
                'status' => 'success',
                'subscription_id' => $subscription_id,
                'message' => 'Subscription cancelled successfully'
            ], JSON_UNESCAPED_UNICODE);
        } else {
            http_response_code(404);
            echo json_encode(['error' => 'Subscription not found or already cancelled'], JSON_UNESCAPED_UNICODE);
        }

    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Cancellation error: ' . $e->getMessage()], JSON_UNESCAPED_UNICODE);
    }
}

function handleSubscriptionList($db) {
    try {
        $customer_id = $_GET['customer_id'] ?? null;
        $status = $_GET['status'] ?? null;
        $limit = (int)($_GET['limit'] ?? 10);
        $offset = (int)($_GET['offset'] ?? 0);

        $where = [];
        $params = [];

        if ($customer_id) {
            $where[] = 's.customer_id = :customer_id';
            $params['customer_id'] = $customer_id;
        }

        if ($status) {
            $where[] = 's.status = :status';
            $params['status'] = $status;
        }

        $whereClause = $where ? 'WHERE ' . implode(' AND ', $where) : '';

        $stmt = $db->prepare(
            "SELECT s.*, p.name as package_name, p.description as package_description
             FROM subscriptions s
             LEFT JOIN publishing_packages p ON s.package_id = p.id
             $whereClause
             ORDER BY s.created_at DESC
             LIMIT :limit OFFSET :offset"
        );

        foreach ($params as $key => $value) {
            $stmt->bindValue(':' . $key, $value);
        }
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);

        $stmt->execute();
        $subscriptions = $stmt->fetchAll(PDO::FETCH_ASSOC);

        echo json_encode(['subscriptions' => $subscriptions], JSON_UNESCAPED_UNICODE);

    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['error' => 'List retrieval error: ' . $e->getMessage()], JSON_UNESCAPED_UNICODE);
    }
}

function handleSubscriptionStatus($db, $subscription_id) {
    try {
        if (!$subscription_id) {
            http_response_code(400);
            echo json_encode(['error' => 'Subscription ID is required'], JSON_UNESCAPED_UNICODE);
            return;
        }

        $stmt = $db->prepare(
            'SELECT s.*, p.name as package_name FROM subscriptions s
             LEFT JOIN publishing_packages p ON s.package_id = p.id
             WHERE s.subscription_id = :id'
        );
        $stmt->execute(['id' => $subscription_id]);
        $subscription = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$subscription) {
            http_response_code(404);
            echo json_encode(['error' => 'Subscription not found'], JSON_UNESCAPED_UNICODE);
            return;
        }

        echo json_encode($subscription, JSON_UNESCAPED_UNICODE);

    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Status retrieval error: ' . $e->getMessage()], JSON_UNESCAPED_UNICODE);
    }
}

function handleSubscriptionUpdate($db) {
    try {
        $data = json_decode(file_get_contents('php://input'), true);
        $subscription_id = $data['subscription_id'] ?? '';

        if (!$subscription_id) {
            http_response_code(400);
            echo json_encode(['error' => 'Subscription ID is required'], JSON_UNESCAPED_UNICODE);
            return;
        }

        $fields = [];
        $params = ['id' => $subscription_id];

        $allowedFields = ['auto_renew', 'payment_method', 'metadata'];

        foreach ($allowedFields as $field) {
            if (array_key_exists($field, $data)) {
                if ($field === 'metadata') {
                    $fields[] = "$field = :$field";
                    $params[$field] = json_encode($data[$field]);
                } else {
                    $fields[] = "$field = :$field";
                    $params[$field] = $data[$field];
                }
            }
        }

        if (empty($fields)) {
            http_response_code(400);
            echo json_encode(['error' => 'No valid fields to update'], JSON_UNESCAPED_UNICODE);
            return;
        }

        $sql = 'UPDATE subscriptions SET ' . implode(', ', $fields) . ', updated_at = CURRENT_TIMESTAMP WHERE subscription_id = :id';
        $stmt = $db->prepare($sql);
        $stmt->execute($params);

        if ($stmt->rowCount() > 0) {
            echo json_encode(['status' => 'success', 'message' => 'Subscription updated successfully'], JSON_UNESCAPED_UNICODE);
        } else {
            http_response_code(404);
            echo json_encode(['error' => 'Subscription not found'], JSON_UNESCAPED_UNICODE);
        }

    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Update error: ' . $e->getMessage()], JSON_UNESCAPED_UNICODE);
    }
}
?>