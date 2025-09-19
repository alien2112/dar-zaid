<?php
require_once 'backend/config/database.php';

try {
    $database = new Database();
    $db = $database->getConnection();
    
    if ($db) {
        echo "Database connection: SUCCESS\n";
        
        // Test moving_bar table
        $stmt = $db->query("SHOW TABLES LIKE 'moving_bar'");
        $tableExists = $stmt->fetch();
        
        if ($tableExists) {
            echo "moving_bar table: EXISTS\n";
            
            // Try to query the table
            $stmt = $db->query('SELECT * FROM moving_bar ORDER BY id DESC LIMIT 1');
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($result) {
                echo "moving_bar data: " . json_encode($result) . "\n";
            } else {
                echo "moving_bar table is empty\n";
            }
        } else {
            echo "moving_bar table: NOT FOUND\n";
        }
    } else {
        echo "Database connection: FAILED\n";
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>
