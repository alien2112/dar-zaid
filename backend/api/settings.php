<?php
// Include centralized CORS configuration
require_once __DIR__ . '/../config/cors.php';
require_once __DIR__ . '/../config/database.php';

$method = $_SERVER['REQUEST_METHOD'];

// Create settings table if not exists
$db->exec("CREATE TABLE IF NOT EXISTS business_settings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    setting_key VARCHAR(255) UNIQUE NOT NULL,
    setting_value TEXT NOT NULL,
    setting_type ENUM('string', 'number', 'boolean', 'json') DEFAULT 'string',
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

// Initialize default settings if table is empty
$stmt = $db->query("SELECT COUNT(*) FROM business_settings");
if ($stmt->fetchColumn() == 0) {
    $defaultSettings = [
        ['shipping_cost', '25', 'number', 'Default shipping cost in SAR'],
        ['free_shipping_threshold', '200', 'number', 'Free shipping threshold in SAR'],
        ['tax_rate', '0.15', 'number', 'Tax rate (15% VAT)'],
        ['currency', 'SAR', 'string', 'Default currency'],
        ['country', 'Saudi Arabia', 'string', 'Default country'],
        ['payment_methods', '["stc_pay", "tamara", "tabby", "google_pay", "apple_pay", "visa", "mastercard", "mada", "bank_transfer"]', 'json', 'Available payment methods'],
        ['moving_bar_text', 'مرحباً بكم في دار زيد للنشر والتوزيع - شحن مجاني لطلبات أكثر من 200 ريال - خصم 15% على الطلبة والأكاديميين', 'string', 'Moving bar text'],
        ['site_name', 'دار زيد للنشر والتوزيع', 'string', 'Site name'],
        ['contact_email', 'info@darzaid.com', 'string', 'Contact email'],
        ['contact_phone', '+966123456789', 'string', 'Contact phone']
    ];

    $stmt = $db->prepare("INSERT INTO business_settings (setting_key, setting_value, setting_type, description) VALUES (?, ?, ?, ?)");
    foreach ($defaultSettings as $setting) {
        $stmt->execute($setting);
    }
}

if ($method === 'GET') {
    try {
        $settings = [];
        $stmt = $db->query("SELECT setting_key, setting_value, setting_type FROM business_settings");
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $value = $row['setting_value'];
            if ($row['setting_type'] === 'number') {
                $value = is_numeric($value) ? (strpos($value, '.') !== false ? (float)$value : (int)$value) : $value;
            } elseif ($row['setting_type'] === 'boolean') {
                $value = filter_var($value, FILTER_VALIDATE_BOOLEAN);
            } elseif ($row['setting_type'] === 'json') {
                $value = json_decode($value, true);
            }
            $settings[$row['setting_key']] = $value;
        }

        echo json_encode(['settings' => $settings], JSON_UNESCAPED_UNICODE);
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Database error: ' . $e->getMessage()], JSON_UNESCAPED_UNICODE);
    }
} elseif ($method === 'POST') {
    try {
        $data = json_decode(file_get_contents('php://input'), true);
        
        if (!isset($data['settings']) || !is_array($data['settings'])) {
            http_response_code(400);
            echo json_encode(['error' => 'Invalid settings data'], JSON_UNESCAPED_UNICODE);
            exit();
        }

        $db->beginTransaction();
        
        foreach ($data['settings'] as $key => $value) {
            $type = 'string';
            if (is_numeric($value)) {
                $type = 'number';
            } elseif (is_bool($value)) {
                $type = 'boolean';
            } elseif (is_array($value) || is_object($value)) {
                $type = 'json';
                $value = json_encode($value);
            }

            $stmt = $db->prepare("
                INSERT INTO business_settings (setting_key, setting_value, setting_type) 
                VALUES (?, ?, ?) 
                ON DUPLICATE KEY UPDATE 
                setting_value = VALUES(setting_value), 
                setting_type = VALUES(setting_type),
                updated_at = CURRENT_TIMESTAMP
            ");
            $stmt->execute([$key, $value, $type]);
        }
        
        $db->commit();
        echo json_encode(['message' => 'Settings updated successfully'], JSON_UNESCAPED_UNICODE);
    } catch (PDOException $e) {
        $db->rollBack();
        http_response_code(500);
        echo json_encode(['error' => 'Database error: ' . $e->getMessage()], JSON_UNESCAPED_UNICODE);
    } catch (Exception $e) {
        $db->rollBack();
        http_response_code(500);
        echo json_encode(['error' => 'Error updating settings: ' . $e->getMessage()], JSON_UNESCAPED_UNICODE);
    }
} else {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed'], JSON_UNESCAPED_UNICODE);
}
?>
