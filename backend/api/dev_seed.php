<?php
require_once __DIR__ . '/../config/database.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: ' . (isset($_SERVER['HTTP_ORIGIN']) ? $_SERVER['HTTP_ORIGIN'] : 'http://localhost:3000'));
header('Vary: Origin');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');
header('Access-Control-Allow-Credentials: true');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(200); exit(); }
if ($_SERVER['REQUEST_METHOD'] !== 'POST') { http_response_code(405); echo json_encode(['error' => 'Method not allowed']); exit(); }

$database = new Database();
$db = $database->getConnection();

$samples = [
    ['العطر', 'باتريك زوسكيند', 'رواية عالمية شهيرة', 49.00, 'أدب', '/images/book1.jpg', '978000000001', '1985-01-01', 20],
    ['رجال في الشمس', 'غسان كنفاني', 'من كلاسيكيات الأدب الفلسطيني', 35.00, 'أدب', '/images/book2.jpg', '978000000002', '1963-01-01', 15],
    ['مقدمة ابن خلدون', 'ابن خلدون', 'علم الاجتماع التاريخي', 70.00, 'تاريخ', '/images/book3.jpg', '978000000003', '1377-01-01', 10],
    ['علم نفس', 'مؤلف مجهول', 'مبادئ علم النفس الحديث', 55.00, 'علوم', '/images/book4.jpg', '978000000004', '2001-05-10', 12],
    ['اقتصاد بسيط', 'جون دو', 'مفاهيم مبسطة في الاقتصاد', 40.00, 'اقتصاد', '/images/book5.jpg', '978000000005', '2010-02-20', 25],
    ['طبخ شرقي', 'شيف عربية', 'أشهى الوصفات المنزلية', 60.00, 'طبخ', '/images/book6.jpg', '978000000006', '2018-08-01', 18],
    ['قصص أطفال', 'كاتب أطفال', 'حكايات ممتعة للصغار', 30.00, 'أطفال', '/images/book7.jpg', '978000000007', '2020-03-15', 40],
    ['رواية تاريخية', 'مبدع عربي', 'رحلة في الماضي', 52.00, 'تاريخ', '/images/book8.jpg', '978000000008', '2015-11-11', 9],
    ['فيزياء للفضوليين', 'عالِم', 'تبسيط مفاهيم الفيزياء', 47.00, 'علوم', '/images/book9.jpg', '978000000009', '2019-09-09', 13],
    ['إدارة الوقت', 'خبير إنتاجية', 'خطط فعّالة ليوم أفضل', 33.00, 'اقتصاد', '/images/book10.jpg', '978000000010', '2017-07-07', 17]
];

try {
    $stmt = $db->prepare('INSERT INTO books (title, author, description, price, category, image_url, isbn, published_date, stock_quantity) VALUES (:title, :author, :description, :price, :category, :image_url, :isbn, :published_date, :stock_quantity)');
    foreach ($samples as $b) {
        $stmt->execute([
            ':title' => $b[0],
            ':author' => $b[1],
            ':description' => $b[2],
            ':price' => $b[3],
            ':category' => $b[4],
            ':image_url' => $b[5],
            ':isbn' => $b[6],
            ':published_date' => $b[7],
            ':stock_quantity' => $b[8]
        ]);
    }
    echo json_encode(['success' => true, 'inserted' => count($samples)]);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'DB: ' . $e->getMessage()]);
}

?>


