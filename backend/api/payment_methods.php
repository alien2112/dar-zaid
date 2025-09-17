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
if ($method === 'OPTIONS') { http_response_code(200); exit(); }

$database = new Database();
$db = $database->getConnection();

// Create payment methods table if not exists
$db->exec("CREATE TABLE IF NOT EXISTS payment_methods (
    id INT AUTO_INCREMENT PRIMARY KEY,
    method_id VARCHAR(255) UNIQUE NOT NULL,
    name VARCHAR(255) NOT NULL,
    name_ar VARCHAR(255) NOT NULL,
    icon VARCHAR(500),
    type ENUM('digital_wallet', 'bnpl', 'credit_debit', 'debit_only', 'bank_transfer') NOT NULL,
    enabled BOOLEAN DEFAULT true,
    supported_currencies JSON NOT NULL,
    min_amount DECIMAL(10, 2) DEFAULT 1,
    max_amount DECIMAL(10, 2) DEFAULT 10000,
    description TEXT,
    processing_time VARCHAR(100),
    installments JSON,
    display_order INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

// Initialize default payment methods if table is empty
$stmt = $db->query("SELECT COUNT(*) FROM payment_methods");
if ($stmt->fetchColumn() == 0) {
    $defaultMethods = [
        [
            'method_id' => 'stc_pay',
            'name' => 'STC Pay',
            'name_ar' => 'إس تي سي باي',
            'icon' => '/images/payments/stc-pay.svg',
            'type' => 'digital_wallet',
            'enabled' => true,
            'supported_currencies' => json_encode(['SAR']),
            'min_amount' => 1,
            'max_amount' => 10000,
            'description' => 'ادفع بسهولة باستخدام STC Pay',
            'processing_time' => 'فوري',
            'installments' => null,
            'display_order' => 1
        ],
        [
            'method_id' => 'tamara',
            'name' => 'Tamara',
            'name_ar' => 'تمارا',
            'icon' => '/images/payments/tamara.svg',
            'type' => 'bnpl',
            'enabled' => true,
            'supported_currencies' => json_encode(['SAR']),
            'min_amount' => 50,
            'max_amount' => 5000,
            'description' => 'اشتري الآن وادفع لاحقاً على 3 دفعات',
            'processing_time' => 'فوري',
            'installments' => json_encode([3, 6]),
            'display_order' => 2
        ],
        [
            'method_id' => 'tabby',
            'name' => 'Tabby',
            'name_ar' => 'تابي',
            'icon' => '/images/payments/tabby.svg',
            'type' => 'bnpl',
            'enabled' => true,
            'supported_currencies' => json_encode(['SAR']),
            'min_amount' => 30,
            'max_amount' => 3000,
            'description' => 'اشتري الآن وادفع لاحقاً على 4 دفعات',
            'processing_time' => 'فوري',
            'installments' => json_encode([4]),
            'display_order' => 3
        ],
        [
            'method_id' => 'google_pay',
            'name' => 'Google Pay',
            'name_ar' => 'جوجل باي',
            'icon' => '/images/payments/google-pay.svg',
            'type' => 'digital_wallet',
            'enabled' => true,
            'supported_currencies' => json_encode(['SAR', 'USD']),
            'min_amount' => 1,
            'max_amount' => 10000,
            'description' => 'ادفع بأمان باستخدام Google Pay',
            'processing_time' => 'فوري',
            'installments' => null,
            'display_order' => 4
        ],
        [
            'method_id' => 'apple_pay',
            'name' => 'Apple Pay',
            'name_ar' => 'آبل باي',
            'icon' => '/images/payments/apple-pay.svg',
            'type' => 'digital_wallet',
            'enabled' => true,
            'supported_currencies' => json_encode(['SAR', 'USD']),
            'min_amount' => 1,
            'max_amount' => 10000,
            'description' => 'ادفع بسهولة باستخدام Apple Pay',
            'processing_time' => 'فوري',
            'installments' => null,
            'display_order' => 5
        ],
        [
            'method_id' => 'bank_transfer',
            'name' => 'Bank Transfer',
            'name_ar' => 'تحويل بنكي',
            'icon' => '/images/payments/bank-transfer.svg',
            'type' => 'bank_transfer',
            'enabled' => true,
            'supported_currencies' => json_encode(['SAR']),
            'min_amount' => 10,
            'max_amount' => 50000,
            'description' => 'تحويل مباشر من البنك الخاص بك',
            'processing_time' => '1-3 أيام عمل',
            'installments' => null,
            'display_order' => 6
        ],
        [
            'method_id' => 'visa',
            'name' => 'Visa',
            'name_ar' => 'فيزا',
            'icon' => '/images/payments/visa.svg',
            'type' => 'credit_debit',
            'enabled' => true,
            'supported_currencies' => json_encode(['SAR', 'USD', 'EUR']),
            'min_amount' => 1,
            'max_amount' => 10000,
            'description' => 'ادفع باستخدام بطاقة الفيزا',
            'processing_time' => 'فوري',
            'installments' => null,
            'display_order' => 7
        ],
        [
            'method_id' => 'mastercard',
            'name' => 'Mastercard',
            'name_ar' => 'ماستركارد',
            'icon' => '/images/payments/mastercard.svg',
            'type' => 'credit_debit',
            'enabled' => true,
            'supported_currencies' => json_encode(['SAR', 'USD', 'EUR']),
            'min_amount' => 1,
            'max_amount' => 10000,
            'description' => 'ادفع باستخدام بطاقة الماستركارد',
            'processing_time' => 'فوري',
            'installments' => null,
            'display_order' => 8
        ],
        [
            'method_id' => 'mada',
            'name' => 'mada',
            'name_ar' => 'مدى',
            'icon' => '/images/payments/mada.svg',
            'type' => 'debit_only',
            'enabled' => true,
            'supported_currencies' => json_encode(['SAR']),
            'min_amount' => 1,
            'max_amount' => 10000,
            'description' => 'ادفع باستخدام بطاقة مدى السعودية',
            'processing_time' => 'فوري',
            'installments' => null,
            'display_order' => 9
        ],
        [
            'method_id' => 'paypal',
            'name' => 'PayPal',
            'name_ar' => 'باي بال',
            'icon' => '/images/payments/paypal.svg',
            'type' => 'digital_wallet',
            'enabled' => true,
            'supported_currencies' => json_encode(['SAR', 'USD', 'EUR', 'GBP']),
            'min_amount' => 1,
            'max_amount' => 50000,
            'description' => 'ادفع بأمان باستخدام PayPal',
            'processing_time' => 'فوري',
            'installments' => null,
            'display_order' => 10
        ],
        [
            'method_id' => 'sadad',
            'name' => 'Sadad',
            'name_ar' => 'سداد',
            'icon' => '/images/payments/sadad.svg',
            'type' => 'bank_transfer',
            'enabled' => true,
            'supported_currencies' => json_encode(['SAR']),
            'min_amount' => 1,
            'max_amount' => 100000,
            'description' => 'ادفع عبر نظام سداد الإلكتروني',
            'processing_time' => 'فوري',
            'installments' => null,
            'display_order' => 11
        ],
        [
            'method_id' => 'fawry',
            'name' => 'Fawry',
            'name_ar' => 'فوري',
            'icon' => '/images/payments/fawry.svg',
            'type' => 'bank_transfer',
            'enabled' => true,
            'supported_currencies' => json_encode(['SAR', 'EGP']),
            'min_amount' => 1,
            'max_amount' => 10000,
            'description' => 'ادفع عبر فوري في المتاجر المشاركة',
            'processing_time' => 'فوري',
            'installments' => null,
            'display_order' => 12
        ],
        [
            'method_id' => 'urpay',
            'name' => 'UrPay',
            'name_ar' => 'أور باي',
            'icon' => '/images/payments/urpay.svg',
            'type' => 'digital_wallet',
            'enabled' => true,
            'supported_currencies' => json_encode(['SAR']),
            'min_amount' => 1,
            'max_amount' => 5000,
            'description' => 'محفظة رقمية سعودية آمنة',
            'processing_time' => 'فوري',
            'installments' => null,
            'display_order' => 13
        ],
        [
            'method_id' => 'benefit',
            'name' => 'Benefit',
            'name_ar' => 'بنفت',
            'icon' => '/images/payments/benefit.svg',
            'type' => 'digital_wallet',
            'enabled' => true,
            'supported_currencies' => json_encode(['SAR']),
            'min_amount' => 1,
            'max_amount' => 10000,
            'description' => 'ادفع باستخدام محفظة بنفت الرقمية',
            'processing_time' => 'فوري',
            'installments' => null,
            'display_order' => 14
        ],
        [
            'method_id' => 'amex',
            'name' => 'American Express',
            'name_ar' => 'أمريكان إكسبريس',
            'icon' => '/images/payments/amex.svg',
            'type' => 'credit_debit',
            'enabled' => true,
            'supported_currencies' => json_encode(['SAR', 'USD', 'EUR']),
            'min_amount' => 1,
            'max_amount' => 10000,
            'description' => 'ادفع باستخدام بطاقة أمريكان إكسبريس',
            'processing_time' => 'فوري',
            'installments' => null,
            'display_order' => 15
        ],
        [
            'method_id' => 'unionpay',
            'name' => 'UnionPay',
            'name_ar' => 'يونيون باي',
            'icon' => '/images/payments/unionpay.svg',
            'type' => 'credit_debit',
            'enabled' => true,
            'supported_currencies' => json_encode(['SAR', 'USD', 'CNY']),
            'min_amount' => 1,
            'max_amount' => 10000,
            'description' => 'ادفع باستخدام بطاقة يونيون باي',
            'processing_time' => 'فوري',
            'installments' => null,
            'display_order' => 16
        ]
    ];

    $stmt = $db->prepare("
        INSERT INTO payment_methods
        (method_id, name, name_ar, icon, type, enabled, supported_currencies, min_amount, max_amount, description, processing_time, installments, display_order)
        VALUES (:method_id, :name, :name_ar, :icon, :type, :enabled, :supported_currencies, :min_amount, :max_amount, :description, :processing_time, :installments, :display_order)
    ");

    foreach ($defaultMethods as $method) {
        $stmt->execute($method);
    }
}

if ($method === 'GET') {
    try {
        $enabled = $_GET['enabled'] ?? null;
        $whereClause = $enabled === 'true' ? 'WHERE enabled = 1' : '';
        
        $query = "SELECT * FROM payment_methods $whereClause ORDER BY display_order, id";
        $stmt = $db->prepare($query);
        $stmt->execute();

        $methods = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $method = [
                'id' => $row['method_id'],
                'name' => $row['name'],
                'nameAr' => $row['name_ar'],
                'icon' => $row['icon'],
                'type' => $row['type'],
                'enabled' => (bool)$row['enabled'],
                'supportedCurrencies' => json_decode($row['supported_currencies'], true),
                'minAmount' => (float)$row['min_amount'],
                'maxAmount' => (float)$row['max_amount'],
                'description' => $row['description'],
                'processingTime' => $row['processing_time'],
                'installments' => $row['installments'] ? json_decode($row['installments'], true) : null,
                'displayOrder' => (int)$row['display_order']
            ];
            $methods[] = $method;
        }

        echo json_encode(['payment_methods' => $methods], JSON_UNESCAPED_UNICODE);
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Database error: ' . $e->getMessage()], JSON_UNESCAPED_UNICODE);
    }
} elseif ($method === 'POST') {
    try {
        $data = json_decode(file_get_contents('php://input'), true);
        
        if (!isset($data['method_id']) || !isset($data['name']) || !isset($data['name_ar'])) {
            http_response_code(400);
            echo json_encode(['error' => 'Missing required fields'], JSON_UNESCAPED_UNICODE);
            exit();
        }

        $stmt = $db->prepare("
            INSERT INTO payment_methods 
            (method_id, name, name_ar, icon, type, enabled, supported_currencies, min_amount, max_amount, description, processing_time, installments, display_order) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE
            name = VALUES(name),
            name_ar = VALUES(name_ar),
            icon = VALUES(icon),
            type = VALUES(type),
            enabled = VALUES(enabled),
            supported_currencies = VALUES(supported_currencies),
            min_amount = VALUES(min_amount),
            max_amount = VALUES(max_amount),
            description = VALUES(description),
            processing_time = VALUES(processing_time),
            installments = VALUES(installments),
            display_order = VALUES(display_order),
            updated_at = CURRENT_TIMESTAMP
        ");
        
        $stmt->execute([
            $data['method_id'],
            $data['name'],
            $data['name_ar'],
            $data['icon'] ?? '',
            $data['type'] ?? 'credit_debit',
            $data['enabled'] ?? true,
            json_encode($data['supported_currencies'] ?? ['SAR']),
            $data['min_amount'] ?? 1,
            $data['max_amount'] ?? 10000,
            $data['description'] ?? '',
            $data['processing_time'] ?? 'فوري',
            isset($data['installments']) ? json_encode($data['installments']) : null,
            $data['display_order'] ?? 0
        ]);

        echo json_encode(['message' => 'Payment method updated successfully'], JSON_UNESCAPED_UNICODE);
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Database error: ' . $e->getMessage()], JSON_UNESCAPED_UNICODE);
    }
} else {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed'], JSON_UNESCAPED_UNICODE);
}
?>
