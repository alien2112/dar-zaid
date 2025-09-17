<?php
require_once '../config/database.php';

try {
    $database = new Database();
    $db = $database->getConnection();

    // Check if moving_bar table exists
    $result = $db->query('SHOW TABLES LIKE "moving_bar"');

    if ($result->rowCount() > 0) {
        echo "Table moving_bar exists\n";
        $result = $db->query('DESCRIBE moving_bar');
        while ($row = $result->fetch(PDO::FETCH_ASSOC)) {
            echo $row['Field'] . ' | ' . $row['Type'] . "\n";
        }
    } else {
        echo "Table moving_bar does not exist. Creating it...\n";

        // Create the table
        $createTable = "CREATE TABLE moving_bar (
            id INT AUTO_INCREMENT PRIMARY KEY,
            text TEXT NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

        $db->exec($createTable);
        echo "Table created successfully\n";

        // Insert default text
        $stmt = $db->prepare('INSERT INTO moving_bar (id, text) VALUES (1, ?)');
        $stmt->execute(['مرحباً بكم في دار زيد للنشر والتوزيع - شحن مجاني لطلبات أكثر من 200 ريال - خصم 15% على الطلبة والأكاديميين']);
        echo "Default text inserted\n";
    }

} catch(PDOException $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>