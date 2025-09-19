<?php
// Include centralized CORS configuration
require_once __DIR__ . '/../config/cors.php';
require_once __DIR__ . '/../config/database.php';

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    try {
        $reportType = $_GET['type'] ?? 'dashboard';

        switch ($reportType) {
            case 'dashboard':
                // Get overall statistics
                $stats = [];

                // Total books
                $stmt = $db->query("SELECT COUNT(*) as total FROM books");
                $stats['total_books'] = (int)$stmt->fetchColumn();

                // Total users
                $stmt = $db->query("SELECT COUNT(*) as total FROM users");
                $stats['total_users'] = (int)$stmt->fetchColumn();

                // Total orders
                $stmt = $db->query("SELECT COUNT(*) as total FROM orders");
                $stats['total_orders'] = (int)$stmt->fetchColumn();

                // Total revenue from orders
                $stmt = $db->query("SELECT COALESCE(SUM(total_amount), 0) as total FROM orders WHERE payment_status = 'paid'");
                $stats['total_revenue'] = (float)$stmt->fetchColumn();

                // Total package purchases
                $stmt = $db->query("SELECT COUNT(*) as total FROM package_purchases");
                $stats['total_package_purchases'] = (int)$stmt->fetchColumn();

                // Total package revenue
                $stmt = $db->query("SELECT COALESCE(SUM(amount_paid), 0) as total FROM package_purchases WHERE status IN ('active', 'completed')");
                $stats['total_package_revenue'] = (float)$stmt->fetchColumn();

                // Recent orders (last 30 days)
                $stmt = $db->query("SELECT COUNT(*) as total FROM orders WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)");
                $stats['recent_orders'] = (int)$stmt->fetchColumn();

                // Recent users (last 30 days)
                $stmt = $db->query("SELECT COUNT(*) as total FROM users WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)");
                $stats['recent_users'] = (int)$stmt->fetchColumn();

                echo json_encode(['stats' => $stats], JSON_UNESCAPED_UNICODE);
                break;

            case 'book_sales':
                // Get book sales statistics
                $stmt = $db->query("
                    SELECT
                        b.id,
                        b.title,
                        b.author,
                        b.price,
                        COALESCE(SUM(oi.quantity), 0) as total_sold,
                        COALESCE(SUM(oi.total_price), 0) as total_revenue,
                        b.stock_quantity
                    FROM books b
                    LEFT JOIN order_items oi ON b.id = oi.book_id
                    LEFT JOIN orders o ON oi.order_id = o.id AND o.payment_status = 'paid'
                    GROUP BY b.id, b.title, b.author, b.price, b.stock_quantity
                    ORDER BY total_sold DESC
                    LIMIT 50
                ");

                $bookSales = [];
                while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                    $bookSales[] = [
                        'id' => (int)$row['id'],
                        'title' => $row['title'],
                        'author' => $row['author'],
                        'price' => (float)$row['price'],
                        'total_sold' => (int)$row['total_sold'],
                        'total_revenue' => (float)$row['total_revenue'],
                        'stock_quantity' => (int)$row['stock_quantity']
                    ];
                }

                echo json_encode(['book_sales' => $bookSales], JSON_UNESCAPED_UNICODE);
                break;

            case 'package_stats':
                // Get package statistics
                $stmt = $db->query("
                    SELECT
                        pp.name,
                        pp.price,
                        COUNT(pu.id) as total_purchases,
                        SUM(CASE WHEN pu.status = 'active' THEN 1 ELSE 0 END) as active_purchases,
                        SUM(CASE WHEN pu.status = 'completed' THEN 1 ELSE 0 END) as completed_purchases,
                        COALESCE(SUM(pu.amount_paid), 0) as total_revenue
                    FROM publishing_packages pp
                    LEFT JOIN package_purchases pu ON pp.id = pu.package_id
                    GROUP BY pp.id, pp.name, pp.price
                    ORDER BY total_purchases DESC
                ");

                $packageStats = [];
                while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                    $packageStats[] = [
                        'name' => $row['name'],
                        'price' => (float)$row['price'],
                        'total_purchases' => (int)$row['total_purchases'],
                        'active_purchases' => (int)$row['active_purchases'],
                        'completed_purchases' => (int)$row['completed_purchases'],
                        'total_revenue' => (float)$row['total_revenue']
                    ];
                }

                echo json_encode(['package_stats' => $packageStats], JSON_UNESCAPED_UNICODE);
                break;

            case 'monthly_revenue':
                // Get monthly revenue for the last 12 months
                $stmt = $db->query("
                    SELECT
                        DATE_FORMAT(created_at, '%Y-%m') as month,
                        COUNT(*) as orders_count,
                        SUM(total_amount) as total_revenue
                    FROM orders
                    WHERE payment_status = 'paid'
                        AND created_at >= DATE_SUB(NOW(), INTERVAL 12 MONTH)
                    GROUP BY DATE_FORMAT(created_at, '%Y-%m')
                    ORDER BY month ASC
                ");

                $monthlyRevenue = [];
                while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                    $monthlyRevenue[] = [
                        'month' => $row['month'],
                        'orders_count' => (int)$row['orders_count'],
                        'total_revenue' => (float)$row['total_revenue']
                    ];
                }

                echo json_encode(['monthly_revenue' => $monthlyRevenue], JSON_UNESCAPED_UNICODE);
                break;

            case 'user_registrations':
                // Get user registrations by month for the last 12 months
                $stmt = $db->query("
                    SELECT
                        DATE_FORMAT(created_at, '%Y-%m') as month,
                        COUNT(*) as registrations
                    FROM users
                    WHERE created_at >= DATE_SUB(NOW(), INTERVAL 12 MONTH)
                    GROUP BY DATE_FORMAT(created_at, '%Y-%m')
                    ORDER BY month ASC
                ");

                $userRegistrations = [];
                while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                    $userRegistrations[] = [
                        'month' => $row['month'],
                        'registrations' => (int)$row['registrations']
                    ];
                }

                echo json_encode(['user_registrations' => $userRegistrations], JSON_UNESCAPED_UNICODE);
                break;

            case 'top_categories':
                // Get sales by category
                $stmt = $db->query("
                    SELECT
                        b.category,
                        COUNT(DISTINCT oi.id) as items_sold,
                        SUM(oi.quantity) as total_quantity,
                        SUM(oi.total_price) as total_revenue
                    FROM books b
                    JOIN order_items oi ON b.id = oi.book_id
                    JOIN orders o ON oi.order_id = o.id AND o.payment_status = 'paid'
                    WHERE b.category IS NOT NULL
                    GROUP BY b.category
                    ORDER BY total_revenue DESC
                ");

                $topCategories = [];
                while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                    $topCategories[] = [
                        'category' => $row['category'],
                        'items_sold' => (int)$row['items_sold'],
                        'total_quantity' => (int)$row['total_quantity'],
                        'total_revenue' => (float)$row['total_revenue']
                    ];
                }

                echo json_encode(['top_categories' => $topCategories], JSON_UNESCAPED_UNICODE);
                break;

            default:
                http_response_code(400);
                echo json_encode(['error' => 'Invalid report type'], JSON_UNESCAPED_UNICODE);
                break;
        }

    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Database error: ' . $e->getMessage()], JSON_UNESCAPED_UNICODE);
    }

} else {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed'], JSON_UNESCAPED_UNICODE);
}
?>