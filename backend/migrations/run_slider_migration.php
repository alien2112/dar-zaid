<?php
require_once '../config/database.php';

try {
    $database = new Database();
    $db = $database->getConnection();

    // Create table first
    $createTable = "CREATE TABLE IF NOT EXISTS slider_images (
        id INT AUTO_INCREMENT PRIMARY KEY,
        title VARCHAR(255) NOT NULL,
        image_url TEXT NOT NULL,
        alt_text VARCHAR(255),
        link_url TEXT,
        description TEXT,
        display_order INT DEFAULT 0,
        is_active BOOLEAN DEFAULT TRUE,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        INDEX idx_display_order (display_order),
        INDEX idx_is_active (is_active)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

    $db->exec($createTable);
    echo "Table created successfully\n";

    // Check table structure
    $result = $db->query('DESCRIBE slider_images');
    echo "Table structure:\n";
    while ($row = $result->fetch(PDO::FETCH_ASSOC)) {
        echo $row['Field'] . " | " . $row['Type'] . "\n";
    }

    // Check if data exists
    $countResult = $db->query('SELECT COUNT(*) as count FROM slider_images');
    $count = $countResult->fetch(PDO::FETCH_ASSOC)['count'];

    if ($count == 0) {
        // Insert default data
        $stmt = $db->prepare("INSERT INTO slider_images (title, image_url, alt_text, display_order, is_active) VALUES (?, ?, ?, ?, ?)");

        $defaultData = [
            ['ترحيب بالزوار', '/images/slider/1.jpg', 'مرحباً بكم في دار زيد للنشر والتوزيع', 1, true],
            ['مجموعة الكتب', '/images/slider/2.jpg', 'اكتشف مجموعتنا الواسعة من الكتب', 2, true],
            ['خدمات النشر', '/images/slider/3.jpg', 'نقدم أفضل خدمات النشر والتوزيع', 3, true]
        ];

        foreach ($defaultData as $data) {
            $stmt->execute($data);
        }

        echo "Default data inserted successfully\n";
    } else {
        echo "Table already has data ($count rows)\n";
    }

} catch(PDOException $e) {
    echo "Migration failed: " . $e->getMessage() . "\n";
}
?>