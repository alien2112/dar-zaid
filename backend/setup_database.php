<?php
// Quick seeding script to populate books with sample data per category
require_once __DIR__ . '/config/database.php';

header('Content-Type: application/json; charset=UTF-8');

try {
    $database = new Database();
    $db = $database->getConnection();

    $categories = ['أدب','تاريخ','علوم','طبخ','أطفال','اقتصاد','روايات','شعر'];
    $insert = $db->prepare('INSERT INTO books (title, author, description, price, category, image_url, stock_quantity, isbn, published_date) VALUES (:title,:author,:description,:price,:category,:image_url,:stock_quantity,:isbn,:published_date)');

    $countPerCategory = 12; // 10-15 approx.
    $totalInserted = 0;
    foreach ($categories as $category) {
        for ($i = 1; $i <= $countPerCategory; $i++) {
            $title = $category . ' كتاب رقم ' . $i;
            $author = 'مؤلف ' . $i;
            $description = 'وصف موجز للكتاب ضمن تصنيف ' . $category . ' رقم ' . $i;
            $price = rand(20, 120);
            $image = '/images/book' . (($i % 10) + 1) . '.jpg';
            $stock = rand(0, 50);
            $isbn = '978-600-' . rand(100000, 999999);
            $published = date('Y-m-d', strtotime('-' . rand(0, 3650) . ' days'));

            $insert->execute([
                'title' => $title,
                'author' => $author,
                'description' => $description,
                'price' => $price,
                'category' => $category,
                'image_url' => $image,
                'stock_quantity' => $stock,
                'isbn' => $isbn,
                'published_date' => $published,
            ]);
            $totalInserted++;
        }
    }

    echo json_encode(['inserted' => $totalInserted], JSON_UNESCAPED_UNICODE);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()], JSON_UNESCAPED_UNICODE);
}
?>

<?php
// Database setup script
require_once 'config/database.php';

try {
    // Connect without database first
    $pdo = new PDO("mysql:host=localhost;charset=utf8mb4", 'root', 'root');
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Create database if not exists
    $pdo->exec("CREATE DATABASE IF NOT EXISTS dar_zaid_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    echo "Database created successfully\n";
    
    // Select the database
    $pdo->exec("USE dar_zaid_db");
    
    // Create tables
    $tables = [
        // Books table
        "CREATE TABLE IF NOT EXISTS books (
            id INT PRIMARY KEY AUTO_INCREMENT,
            title VARCHAR(500) NOT NULL,
            author VARCHAR(255) NOT NULL,
            description TEXT,
            price DECIMAL(10, 2) NOT NULL,
            category VARCHAR(100),
            image_url VARCHAR(500),
            isbn VARCHAR(20),
            published_date DATE,
            stock_quantity INT DEFAULT 0,
            status ENUM('available', 'out_of_stock', 'coming_soon') DEFAULT 'available',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        )",
        
        // Blog posts table
        "CREATE TABLE IF NOT EXISTS blog_posts (
            id INT PRIMARY KEY AUTO_INCREMENT,
            title VARCHAR(500) NOT NULL,
            content TEXT NOT NULL,
            excerpt TEXT,
            author VARCHAR(255) NOT NULL,
            image_url VARCHAR(500),
            published_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            status ENUM('draft', 'published') DEFAULT 'published',
            views INT DEFAULT 0,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        )",
        
        // Contact messages table
        "CREATE TABLE IF NOT EXISTS contact_messages (
            id INT PRIMARY KEY AUTO_INCREMENT,
            name VARCHAR(255) NOT NULL,
            email VARCHAR(255) NOT NULL,
            phone VARCHAR(20),
            subject VARCHAR(500),
            message TEXT NOT NULL,
            status ENUM('new', 'read', 'replied') DEFAULT 'new',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )",
        
        // Users table
        "CREATE TABLE IF NOT EXISTS users (
            id INT PRIMARY KEY AUTO_INCREMENT,
            name VARCHAR(255) NOT NULL,
            email VARCHAR(255) UNIQUE NOT NULL,
            password VARCHAR(255) NOT NULL,
            role ENUM('admin', 'author', 'editor', 'user') DEFAULT 'user',
            status ENUM('active', 'inactive') DEFAULT 'active',
            last_login TIMESTAMP NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        )",
        
        // Publishing packages table
        "CREATE TABLE IF NOT EXISTS publishing_packages (
            id INT PRIMARY KEY AUTO_INCREMENT,
            name VARCHAR(255) NOT NULL,
            price DECIMAL(10, 2) NOT NULL,
            currency VARCHAR(10) DEFAULT 'ريال',
            author_share VARCHAR(10) DEFAULT '70%',
            free_copies INT DEFAULT 20,
            description TEXT,
            specifications JSON,
            services JSON,
            additional_services JSON,
            additional_offers TEXT,
            is_active BOOLEAN DEFAULT TRUE,
            display_order INT DEFAULT 0,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        )",
        
        // News and releases table
        "CREATE TABLE IF NOT EXISTS news_releases (
            id INT PRIMARY KEY AUTO_INCREMENT,
            title VARCHAR(500) NOT NULL,
            content TEXT NOT NULL,
            type ENUM('news', 'release', 'event') DEFAULT 'news',
            image_url VARCHAR(500),
            published_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            status ENUM('draft', 'published') DEFAULT 'published',
            featured BOOLEAN DEFAULT FALSE,
            views INT DEFAULT 0,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        )"
    ];
    
    foreach ($tables as $table) {
        $pdo->exec($table);
    }
    echo "Tables created successfully\n";
    
    // Insert sample data
    $insertQueries = [
        // Users
        "INSERT IGNORE INTO users (id, name, email, password, role) VALUES 
        (1, 'مدير النظام', 'admin@darzaid.com', '" . password_hash('admin123', PASSWORD_DEFAULT) . "', 'admin')",
        
        // Books
        "INSERT IGNORE INTO books (id, title, author, description, price, category, stock_quantity, status) VALUES 
        (1, 'الأدب العربي في العصر الحديث', 'د. أحمد محمد الكاتب', 'دراسة شاملة ومعمقة للأدب العربي في العصر الحديث، تتناول أبرز الأعلام والمدارس الأدبية والتطورات الفكرية التي شهدها الأدب العربي منذ بداية القرن التاسع عشر حتى اليوم.', 65.00, 'أدب', 150, 'available'),
        (2, 'تاريخ المملكة العربية السعودية: التأسيس والتطور', 'د. محمد علي المؤرخ', 'سرد تاريخي شامل لتأسيس المملكة العربية السعودية على يد الملك عبدالعزيز آل سعود، ومراحل تطورها عبر العقود، مع التركيز على الإنجازات الحضارية والتنموية.', 80.00, 'تاريخ', 200, 'available'),
        (3, 'علم النفس التربوي: النظرية والتطبيق', 'د. سارة أحمد الطبيبة', 'مرجع شامل في علم النفس التربوي يغطي النظريات الحديثة وتطبيقاتها العملية في الميدان التعليمي، مع أمثلة وحالات دراسية من البيئة التعليمية العربية.', 70.00, 'علوم', 100, 'available'),
        (4, 'الاقتصاد الإسلامي في العصر الحديث', 'د. عبدالله الاقتصادي', 'تحليل معاصر لمبادئ الاقتصاد الإسلامي وتطبيقاته في النظم المصرفية والمالية الحديثة، مع دراسة للتجارب الناجحة في الدول الإسلامية.', 90.00, 'اقتصاد', 80, 'available'),
        (5, 'الشعر السعودي المعاصر: دراسة نقدية', 'د. فاطمة النقدية', 'دراسة نقدية شاملة للشعر السعودي المعاصر منذ تأسيس المملكة، تتناول أبرز الشعراء والقصائد والاتجاهات الشعرية مع التحليل الفني والموضوعي.', 55.00, 'أدب', 120, 'available')",
        
        // Blog posts
        "INSERT IGNORE INTO blog_posts (id, title, content, excerpt, author) VALUES 
        (1, 'أهمية القراءة في تنمية الفكر والثقافة', 'القراءة هي غذاء العقل والروح، وهي الوسيلة الأهم لتنمية المعرفة والثقافة الإنسانية. في عصرنا الحالي، وسط زحمة التكنولوجيا ووسائل التواصل الاجتماعي، تبقى القراءة هي الطريق الأمثل لبناء شخصية متوازنة ومثقفة. إن الكتاب يفتح آفاقاً واسعة أمام القارئ، ويأخذه في رحلة عبر الزمن والمكان، يتعرف من خلالها على ثقافات مختلفة وأفكار متنوعة. والقراءة لا تقتصر على نوع واحد من الكتب، بل تشمل الأدب والتاريخ والعلوم والفلسفة وغيرها من المجالات المعرفية.', 'القراءة هي غذاء العقل والروح، وهي الوسيلة الأهم لتنمية المعرفة والثقافة...', 'فريق التحرير'),
        (2, 'دور النشر في دعم الكتاب والمؤلفين الجدد', 'تلعب دور النشر دوراً محورياً في اكتشاف المواهب الأدبية والفكرية الجديدة ودعمها. فهي تعمل كجسر يربط بين المؤلف والقارئ، وتوفر المنصة المناسبة لإيصال الأفكار والإبداعات إلى الجمهور الواسع. إن دار النشر المتميزة لا تكتفي بطباعة الكتب وتوزيعها، بل تقدم خدمات شاملة تشمل التحرير والتدقيق والتصميم والتسويق. وفي دار زيد للنشر والتوزيع، نؤمن بأهمية دعم المواهب الشابة وتقديم الإرشاد والمساعدة اللازمة لهم لإخراج أعمالهم بأفضل صورة ممكنة.', 'تلعب دور النشر دوراً محورياً في اكتشاف المواهب الأدبية الجديدة...', 'إدارة التحرير'),
        (3, 'التطورات في صناعة النشر الرقمي', 'شهدت صناعة النشر تطورات جذرية مع ظهور التكنولوجيا الرقمية والكتب الإلكترونية. هذه التطورات لم تغير فقط من طريقة إنتاج الكتب وتوزيعها، بل أيضاً من طريقة قراءتها واستهلاكها. الكتب الإلكترونية جعلت المعرفة أكثر إتاحة وأسهل وصولاً، كما فتحت آفاقاً جديدة للمؤلفين للوصول إلى جمهور أوسع. في دار زيد، نواكب هذه التطورات ونسعى لتقديم خدمات النشر الرقمي إلى جانب النشر التقليدي.', 'شهدت صناعة النشر تطورات جذرية مع ظهور التكنولوجيا الرقمية...', 'قسم التطوير')",
        
        // Publishing packages
        "INSERT IGNORE INTO publishing_packages (id, name, price, specifications, services, additional_services, additional_offers, display_order) VALUES 
        (1, 'باقة الكتب أبيض واسود', 3500.00, '{\"printing\": \"أبيض وأسود (لون واحد)\", \"size\": \"20×14 سم\", \"paperType\": \"ورق عادي 80جم أبيض/بيج\", \"coverType\": \"ورق مسفلن مطفي ملون 300جم\", \"maxPages\": 300}', '[\"التدقيق اللغوي\", \"التصميم الداخلي\", \"تصميم الغلاف\", \"الفسح من وزارة الإعلام\", \"إصدار الرقم الدولي (الردمك) من مكتبة الملك فهد الوطنية\", \"إصدار شهادة الإيداع من مكتبة الملك فهد الوطنية\", \"الطباعة والنشر\"]', '[\"نشر الكتاب على متجر دار زيد للنشر والتوزيع\", \"طباعة ونشر 1000 نسخة خلال الفترة التعاقدية وفق متطلبات السوق\", \"عرض الكتاب في منافذ البيع\", \"المشاركة في المعارض\"]', 'في حال رغبة المؤلف في الحصول على نسخ إضافية، سيحصل على خصم 70% من سعر الكتاب', 1),
        (2, 'باقة الكتب الملونة', 4500.00, '{\"printing\": \"ملون\", \"size\": \"20×14 سم\", \"paperType\": \"ورق عادي 80جم أبيض/بيج\", \"coverType\": \"ورق مسفلن مطفي ملون 300جم\", \"maxPages\": 300}', '[\"التدقيق اللغوي\", \"التصميم الداخلي\", \"تصميم الغلاف\", \"الفسح من وزارة الإعلام\", \"إصدار الرقم الدولي (الردمك) من مكتبة الملك فهد الوطنية\", \"إصدار شهادة الإيداع من مكتبة الملك فهد الوطنية\", \"الطباعة والنشر\"]', '[\"نشر الكتاب على متجرنا الالكتروني\", \"نشر الكتاب في مكتبات منصة المؤلف السعودي في اكثر من ١٠٠ موقع\", \"طباعة ونشر ١٠٠٠ نسخة خلال الفترة التعاقدية وفق متطلبات السوق\", \"عرض الكتاب في منافذ البيع\", \"المشاركة في المعارض\"]', 'في حال رغبة المؤلف في الحصول على نسخ إضافية، سيحصل على خصم 70% من سعر الكتاب', 2)",
        
        // News and releases
        "INSERT IGNORE INTO news_releases (id, title, content, type, featured) VALUES 
        (1, 'إصدار جديد: الأدب العربي في العصر الحديث', 'صدر حديثاً عن دار زيد للنشر والتوزيع كتاب \"الأدب العربي في العصر الحديث\" للدكتور أحمد محمد الكاتب. يعد هذا الكتاب إضافة مهمة للمكتبة العربية، حيث يتناول بالدراسة والتحليل أبرز الاتجاهات الأدبية في العصر الحديث.', 'release', TRUE),
        (2, 'مشاركة دار زيد في معرض الرياض الدولي للكتاب 2024', 'تشارك دار زيد للنشر والتوزيع في معرض الرياض الدولي للكتاب 2024 بأحدث إصداراتها في مختلف المجالات الأدبية والعلمية. يمكن للزوار زيارة جناحنا رقم 45 في القاعة الرئيسية للاطلاع على كامل مجموعة إصداراتنا.', 'event', TRUE),
        (3, 'توقيع اتفاقية شراكة مع منصة المؤلف السعودي', 'وقعت دار زيد للنشر والتوزيع اتفاقية شراكة استراتيجية مع منصة المؤلف السعودي للثقافة والترفيه، مما يعزز من قدرة الدار على الوصول إلى شريحة أوسع من القراء وتوفير منصات متنوعة لعرض وتوزيع إصداراتها.', 'news', FALSE)"
    ];
    
    foreach ($insertQueries as $query) {
        $pdo->exec($query);
    }
    echo "Sample data inserted successfully\n";
    echo "Database setup completed successfully!\n";
    
} catch(PDOException $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>
