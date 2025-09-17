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

// Create uploads tracking table
createUploadsTable($db);

$request_uri = $_SERVER['REQUEST_URI'];
$path_parts = explode('/', trim(parse_url($request_uri, PHP_URL_PATH), '/'));
$endpoint = $path_parts[count($path_parts) - 1] ?? '';

switch ($method) {
    case 'POST':
        if ($endpoint === 'upload' || empty($endpoint)) {
            handleImageUpload($db);
        } elseif ($endpoint === 'multiple') {
            handleMultipleImageUpload($db);
        } else {
            http_response_code(404);
            echo json_encode(['error' => 'Endpoint not found'], JSON_UNESCAPED_UNICODE);
        }
        break;

    case 'GET':
        if ($endpoint === 'list') {
            handleImageList($db);
        } elseif ($endpoint === 'cleanup') {
            handleCleanupUnusedImages($db);
        } else {
            http_response_code(404);
            echo json_encode(['error' => 'Endpoint not found'], JSON_UNESCAPED_UNICODE);
        }
        break;

    case 'DELETE':
        if (isset($_GET['image_id'])) {
            handleImageDelete($db, $_GET['image_id']);
        } else {
            http_response_code(400);
            echo json_encode(['error' => 'Image ID is required'], JSON_UNESCAPED_UNICODE);
        }
        break;

    default:
        http_response_code(405);
        echo json_encode(['error' => 'Method not allowed'], JSON_UNESCAPED_UNICODE);
}

function createUploadsTable($db) {
    $db->exec("CREATE TABLE IF NOT EXISTS uploaded_images (
        id INT AUTO_INCREMENT PRIMARY KEY,
        filename VARCHAR(255) NOT NULL,
        original_name VARCHAR(255) NOT NULL,
        file_path VARCHAR(500) NOT NULL,
        public_url VARCHAR(500) NOT NULL,
        mime_type VARCHAR(100) NOT NULL,
        file_size INT NOT NULL,
        width INT DEFAULT NULL,
        height INT DEFAULT NULL,
        upload_type ENUM('book_cover', 'blog_image', 'slider_image', 'general') DEFAULT 'general',
        entity_id INT DEFAULT NULL,
        entity_type VARCHAR(50) DEFAULT NULL,
        is_active BOOLEAN DEFAULT TRUE,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        INDEX idx_filename (filename),
        INDEX idx_entity (entity_type, entity_id),
        INDEX idx_upload_type (upload_type),
        INDEX idx_active (is_active)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
}

function handleImageUpload($db) {
    try {
        error_log("handleImageUpload: Received request.");
        error_log("FILES: " . print_r($_FILES, true));
        error_log("POST: " . print_r($_POST, true));

        // Check if file was uploaded
        if (isset($_FILES['image'])) {
            $file = $_FILES['image'];
        } elseif (isset($_FILES['images']) && !empty($_FILES['images']['name'][0])) {
            $file = [
                'name' => $_FILES['images']['name'][0],
                'type' => $_FILES['images']['type'][0],
                'tmp_name' => $_FILES['images']['tmp_name'][0],
                'error' => $_FILES['images']['error'][0],
                'size' => $_FILES['images']['size'][0]
            ];
        } else {
            http_response_code(400);
            echo json_encode(['error' => 'No image file uploaded. Use form field name "image" or "images".'], JSON_UNESCAPED_UNICODE);
            error_log("handleImageUpload: No image file uploaded.");
            return;
        }

        error_log("handleImageUpload: File data: " . print_r($file, true));

        $upload_type = $_POST['upload_type'] ?? 'general';
        $entity_id = isset($_POST['entity_id']) ? (int)$_POST['entity_id'] : null;
        $entity_type = $_POST['entity_type'] ?? null;
        $entity_title = $_POST['entity_title'] ?? null; // Title for naming the file

        // Validate upload error
        if ($file['error'] !== UPLOAD_ERR_OK) {
            $error_messages = [
                UPLOAD_ERR_INI_SIZE => 'File too large (exceeds upload_max_filesize)',
                UPLOAD_ERR_FORM_SIZE => 'File too large (exceeds MAX_FILE_SIZE)',
                UPLOAD_ERR_PARTIAL => 'File was only partially uploaded',
                UPLOAD_ERR_NO_FILE => 'No file was uploaded',
                UPLOAD_ERR_NO_TMP_DIR => 'Missing temporary folder',
                UPLOAD_ERR_CANT_WRITE => 'Failed to write file to disk',
                UPLOAD_ERR_EXTENSION => 'Upload stopped by extension'
            ];

            error_log("handleImageUpload: Upload failed - " . ($error_messages[$file['error']] ?? 'Unknown error'));
            return;
        }

        // Validate file size (10MB max)
        $maxSize = 10 * 1024 * 1024; // 10MB
        if ($file['size'] > $maxSize) {
            http_response_code(400);
            echo json_encode(['error' => 'File too large. Maximum size is 10MB.'], JSON_UNESCAPED_UNICODE);
            error_log("handleImageUpload: File too large - " . $file['size'] . " bytes.");
            return;
        }

        // Validate MIME type
        $allowedMime = [
            'image/jpeg' => 'jpg',
            'image/jpg' => 'jpg',
            'image/png' => 'png',
            'image/webp' => 'webp',
            'image/gif' => 'gif'
        ];

        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $detectedMime = $finfo->file($file['tmp_name']);
        error_log("handleImageUpload: Detected MIME type: " . $detectedMime);

        if (!isset($allowedMime[$detectedMime])) {
            http_response_code(400);
            echo json_encode([
                'error' => 'Unsupported file type. Allowed: JPEG, PNG, WebP, GIF',
                'detected_type' => $detectedMime
            ], JSON_UNESCAPED_UNICODE);
            error_log("handleImageUpload: Unsupported file type - " . $detectedMime);
            return;
        }

        // Additional security checks
        if (!isValidImage($file['tmp_name'])) {
            http_response_code(400);
            echo json_encode(['error' => 'Invalid or corrupted image file'], JSON_UNESCAPED_UNICODE);
            error_log("handleImageUpload: Invalid or corrupted image file.");
            return;
        }

        // Create upload directories organized by type
        $uploadBaseDir = dirname(__DIR__) . '/uploads';

        // Map upload types to folder names
        $typeFolders = [
            'slider_image' => 'slider_images',
            'book_cover' => 'books_images',
            'blog_image' => 'blog_images',
            'news_image' => 'news_images',
            'package_image' => 'packages_images',
            'general' => 'general'
        ];

        $typeFolder = $typeFolders[$upload_type] ?? 'general';
        $uploadDir = $uploadBaseDir . '/' . $typeFolder;
        error_log("handleImageUpload: Upload directory: " . $uploadDir);

        if (!is_dir($uploadDir)) {
            error_log("handleImageUpload: Creating directory: " . $uploadDir);
            if (!mkdir($uploadDir, 0755, true)) {
                http_response_code(500);
                echo json_encode(['error' => 'Failed to create upload directory'], JSON_UNESCAPED_UNICODE);
                error_log("handleImageUpload: Failed to create upload directory: " . $uploadDir);
                return;
            }
        }

        // Generate meaningful filename based on title or fallback to secure name
        $ext = $allowedMime[$detectedMime];
        $filename = '';
        if ($entity_title) {
            // Use title for filename, sanitized
            $safeName = sanitizeFilename($entity_title);
            $filename = $safeName . '.' . $ext;

            // Check if file exists and add counter if needed
            $counter = 1;
            $originalFilename = $filename;
            while (file_exists($uploadDir . '/' . $filename)) {
                $filename = pathinfo($originalFilename, PATHINFO_FILENAME) . '_' . $counter . '.' . $ext;
                $counter++;
            }
        } else {
            // Fallback to secure filename
            $filename = generateSecureFilename($file['name'], $ext);
        }
        $destPath = $uploadDir . '/' . $filename;
        error_log("handleImageUpload: Destination path: " . $destPath);

        // Move uploaded file
        if (!move_uploaded_file($file['tmp_name'], $destPath)) {
            http_response_code(500);
            echo json_encode(['error' => 'Failed to save uploaded file'], JSON_UNESCAPED_UNICODE);
            error_log("handleImageUpload: Failed to move uploaded file from " . $file['tmp_name'] . " to " . $destPath);
            return;
        }
        error_log("handleImageUpload: File moved successfully to " . $destPath);

        // Get image dimensions
        $imageInfo = getimagesize($destPath);
        $width = $imageInfo ? $imageInfo[0] : null;
        $height = $imageInfo ? $imageInfo[1] : null;
        error_log("handleImageUpload: Image dimensions - Width: " . ($width ?? 'N/A') . ", Height: " . ($height ?? 'N/A'));

        // Create optimized versions if needed
        $optimizedPath = createOptimizedVersions($destPath, $upload_type);
        error_log("handleImageUpload: Optimized path: " . $optimizedPath);

        // Generate public URLs
        $relativePath = '/uploads/' . $typeFolder . '/' . $filename;
        $origin = getOriginUrl();
        $publicUrl = rtrim($origin, '/') . $relativePath;
        error_log("handleImageUpload: Public URL: " . $publicUrl);

        // Save to database
        $stmt = $db->prepare(
            'INSERT INTO uploaded_images (filename, original_name, file_path, public_url, mime_type,
                                        file_size, width, height, upload_type, entity_id, entity_type)
             VALUES (:filename, :original_name, :file_path, :public_url, :mime_type,
                     :file_size, :width, :height, :upload_type, :entity_id, :entity_type)'
        );

        $stmt->execute([
            'filename' => $filename,
            'original_name' => $file['name'],
            'file_path' => $destPath,
            'public_url' => $publicUrl,
            'mime_type' => $detectedMime,
            'file_size' => $file['size'],
            'width' => $width,
            'height' => $height,
            'upload_type' => $upload_type,
            'entity_id' => $entity_id,
            'entity_type' => $entity_type
        ]);
        error_log("handleImageUpload: Database insert successful.");

        $imageId = $db->lastInsertId();

        http_response_code(201);
        echo json_encode([
            'success' => true,
            'image_id' => $imageId,
            'url' => $publicUrl,
            'path' => $relativePath,
            'filename' => $filename,
            'width' => $width,
            'height' => $height,
            'file_size' => $file['size'],
            'mime_type' => $detectedMime
        ], JSON_UNESCAPED_UNICODE);
        error_log("handleImageUpload: Image upload successful. Image ID: " . $imageId);

    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Upload error: ' . $e->getMessage()], JSON_UNESCAPED_UNICODE);
        error_log("handleImageUpload: Exception caught - " . $e->getMessage() . " in " . $e->getFile() . " on line " . $e->getLine());
    }
}

function handleMultipleImageUpload($db) {
    try {
        if (!isset($_FILES['images'])) {
            http_response_code(400);
            echo json_encode(['error' => 'No image files uploaded'], JSON_UNESCAPED_UNICODE);
            return;
        }

        $files = $_FILES['images'];
        $results = [];
        $errors = [];

        // Handle multiple files
        $fileCount = is_array($files['name']) ? count($files['name']) : 1;

        for ($i = 0; $i < $fileCount; $i++) {
            try {
                // Create single file array for processing
                $singleFile = [
                    'name' => is_array($files['name']) ? $files['name'][$i] : $files['name'],
                    'type' => is_array($files['type']) ? $files['type'][$i] : $files['type'],
                    'tmp_name' => is_array($files['tmp_name']) ? $files['tmp_name'][$i] : $files['tmp_name'],
                    'error' => is_array($files['error']) ? $files['error'][$i] : $files['error'],
                    'size' => is_array($files['size']) ? $files['size'][$i] : $files['size']
                ];

                // Temporarily set $_FILES for single upload processing
                $_FILES['image'] = $singleFile;

                ob_start();
                handleImageUpload($db);
                $output = ob_get_clean();

                $result = json_decode($output, true);
                if (isset($result['success'])) {
                    $results[] = $result;
                } else {
                    $errors[] = [
                        'file' => $singleFile['name'],
                        'error' => $result['error'] ?? 'Unknown error'
                    ];
                }

            } catch (Exception $e) {
                $errors[] = [
                    'file' => $singleFile['name'] ?? 'Unknown',
                    'error' => $e->getMessage()
                ];
            }
        }

        echo json_encode([
            'success' => count($results) > 0,
            'uploaded' => $results,
            'errors' => $errors,
            'total_files' => $fileCount,
            'successful_uploads' => count($results)
        ], JSON_UNESCAPED_UNICODE);

    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Multiple upload error: ' . $e->getMessage()], JSON_UNESCAPED_UNICODE);
    }
}

function handleImageList($db) {
    try {
        $upload_type = $_GET['upload_type'] ?? null;
        $entity_type = $_GET['entity_type'] ?? null;
        $entity_id = isset($_GET['entity_id']) ? (int)$_GET['entity_id'] : null;
        $limit = (int)($_GET['limit'] ?? 50);
        $offset = (int)($_GET['offset'] ?? 0);

        $where = ['is_active = 1'];
        $params = [];

        if ($upload_type) {
            $where[] = 'upload_type = :upload_type';
            $params['upload_type'] = $upload_type;
        }

        if ($entity_type) {
            $where[] = 'entity_type = :entity_type';
            $params['entity_type'] = $entity_type;
        }

        if ($entity_id) {
            $where[] = 'entity_id = :entity_id';
            $params['entity_id'] = $entity_id;
        }

        $whereClause = 'WHERE ' . implode(' AND ', $where);

        $stmt = $db->prepare(
            "SELECT id, filename, original_name, public_url, mime_type, file_size,
                    width, height, upload_type, entity_id, entity_type, created_at
             FROM uploaded_images $whereClause
             ORDER BY created_at DESC
             LIMIT :limit OFFSET :offset"
        );

        foreach ($params as $key => $value) {
            $stmt->bindValue(':' . $key, $value);
        }
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);

        $stmt->execute();
        $images = $stmt->fetchAll(PDO::FETCH_ASSOC);

        echo json_encode(['images' => $images], JSON_UNESCAPED_UNICODE);

    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Image list error: ' . $e->getMessage()], JSON_UNESCAPED_UNICODE);
    }
}

function handleImageDelete($db, $imageId) {
    try {
        $stmt = $db->prepare('SELECT file_path FROM uploaded_images WHERE id = :id AND is_active = 1');
        $stmt->execute(['id' => $imageId]);
        $image = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$image) {
            http_response_code(404);
            echo json_encode(['error' => 'Image not found'], JSON_UNESCAPED_UNICODE);
            return;
        }

        // Mark as inactive (soft delete)
        $updateStmt = $db->prepare('UPDATE uploaded_images SET is_active = 0, updated_at = CURRENT_TIMESTAMP WHERE id = :id');
        $updateStmt->execute(['id' => $imageId]);

        // Optionally delete physical file (commented out for safety)
        // if (file_exists($image['file_path'])) {
        //     unlink($image['file_path']);
        // }

        echo json_encode(['success' => true, 'message' => 'Image deleted successfully'], JSON_UNESCAPED_UNICODE);

    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Delete error: ' . $e->getMessage()], JSON_UNESCAPED_UNICODE);
    }
}

function handleCleanupUnusedImages($db) {
    try {
        // Find images older than 30 days that are not linked to any entity
        $stmt = $db->prepare(
            'SELECT id, file_path FROM uploaded_images
             WHERE is_active = 1
             AND entity_id IS NULL
             AND created_at < DATE_SUB(NOW(), INTERVAL 30 DAY)'
        );
        $stmt->execute();
        $unusedImages = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $cleaned = 0;
        foreach ($unusedImages as $image) {
            $updateStmt = $db->prepare('UPDATE uploaded_images SET is_active = 0 WHERE id = :id');
            $updateStmt->execute(['id' => $image['id']]);
            $cleaned++;
        }

        echo json_encode([
            'success' => true,
            'cleaned_count' => $cleaned,
            'message' => "Cleaned up $cleaned unused images"
        ], JSON_UNESCAPED_UNICODE);

    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Cleanup error: ' . $e->getMessage()], JSON_UNESCAPED_UNICODE);
    }
}

function generateSecureFilename($originalName, $extension) {
    $timestamp = time();
    $random = bin2hex(random_bytes(8));
    $safeName = preg_replace('/[^a-zA-Z0-9_-]/', '', pathinfo($originalName, PATHINFO_FILENAME));
    $safeName = substr($safeName, 0, 20); // Limit length

    return $timestamp . '_' . $random . '_' . ($safeName ?: 'upload') . '.' . $extension;
}

function isValidImage($filePath) {
    // Check if it's a valid image using getimagesize (works without GD)
    $imageInfo = @getimagesize($filePath);
    if (!$imageInfo) {
        return false;
    }

    // Check for valid image types
    $allowedTypes = [IMAGETYPE_JPEG, IMAGETYPE_PNG, IMAGETYPE_WEBP, IMAGETYPE_GIF];
    if (!in_array($imageInfo[2], $allowedTypes)) {
        return false;
    }

    // Additional basic validation: check file header
    $handle = fopen($filePath, 'rb');
    if (!$handle) {
        return false;
    }

    $header = fread($handle, 16);
    fclose($handle);

    // Check for common image file signatures
    $signatures = [
        "\xFF\xD8\xFF", // JPEG
        "\x89\x50\x4E\x47\x0D\x0A\x1A\x0A", // PNG
        "GIF87a", // GIF87a
        "GIF89a", // GIF89a
        "RIFF", // WebP (starts with RIFF)
    ];

    foreach ($signatures as $signature) {
        if (strpos($header, $signature) === 0) {
            return true;
        }
    }

    // For WebP, check WEBP signature after RIFF
    if (strpos($header, "RIFF") === 0 && strpos($header, "WEBP") !== false) {
        return true;
    }

    return false;
}

function createOptimizedVersions($imagePath, $uploadType) {
    // TODO: Create thumbnails when GD extension is available
    // For now, return original path to avoid GD dependency issues
    return $imagePath;
}

function createThumbnail($sourcePath, $destPath, $maxWidth, $maxHeight) {
    $imageInfo = getimagesize($sourcePath);
    if (!$imageInfo) return false;

    $sourceWidth = $imageInfo[0];
    $sourceHeight = $imageInfo[1];
    $imageType = $imageInfo[2];

    // Calculate new dimensions
    $ratio = min($maxWidth / $sourceWidth, $maxHeight / $sourceHeight);
    $newWidth = round($sourceWidth * $ratio);
    $newHeight = round($sourceHeight * $ratio);

    // Create source image
    switch ($imageType) {
        case IMAGETYPE_JPEG:
            $sourceImage = imagecreatefromjpeg($sourcePath);
            break;
        case IMAGETYPE_PNG:
            $sourceImage = imagecreatefrompng($sourcePath);
            break;
        case IMAGETYPE_WEBP:
            $sourceImage = imagecreatefromwebp($sourcePath);
            break;
        case IMAGETYPE_GIF:
            $sourceImage = imagecreatefromgif($sourcePath);
            break;
        default:
            return false;
    }

    if (!$sourceImage) return false;

    // Create destination image
    $destImage = imagecreatetruecolor($newWidth, $newHeight);

    // Preserve transparency for PNG and GIF
    if ($imageType == IMAGETYPE_PNG || $imageType == IMAGETYPE_GIF) {
        imagealphablending($destImage, false);
        imagesavealpha($destImage, true);
        $transparent = imagecolorallocatealpha($destImage, 255, 255, 255, 127);
        imagefilledrectangle($destImage, 0, 0, $newWidth, $newHeight, $transparent);
    }

    // Resize
    imagecopyresampled($destImage, $sourceImage, 0, 0, 0, 0, $newWidth, $newHeight, $sourceWidth, $sourceHeight);

    // Save
    $result = false;
    switch ($imageType) {
        case IMAGETYPE_JPEG:
            $result = imagejpeg($destImage, $destPath, 85);
            break;
        case IMAGETYPE_PNG:
            $result = imagepng($destImage, $destPath, 8);
            break;
        case IMAGETYPE_WEBP:
            $result = imagewebp($destImage, $destPath, 85);
            break;
        case IMAGETYPE_GIF:
            $result = imagegif($destImage, $destPath);
            break;
    }

    imagedestroy($sourceImage);
    imagedestroy($destImage);

    return $result;
}

function getOriginUrl() {
    $scheme = isset($_SERVER['REQUEST_SCHEME']) ? $_SERVER['REQUEST_SCHEME'] :
              (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http');
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost:8000';

    return $scheme . '://' . $host;
}

function sanitizeFilename($filename) {
    // Remove Arabic diacritics and special characters
    $filename = str_replace([
        'أ', 'إ', 'آ', 'ا', 'ب', 'ت', 'ث', 'ج', 'ح', 'خ', 'د', 'ذ', 'ر', 'ز', 'س', 'ش', 'ص', 'ض', 'ط', 'ظ', 'ع', 'غ', 'ف', 'ق', 'ك', 'ل', 'م', 'ن', 'ه', 'و', 'ي', 'ى', 'ة', 'ء'
    ], [
        'a', 'a', 'a', 'a', 'b', 't', 'th', 'j', 'h', 'kh', 'd', 'th', 'r', 'z', 's', 'sh', 's', 'd', 't', 'z', 'a', 'gh', 'f', 'q', 'k', 'l', 'm', 'n', 'h', 'w', 'y', 'y', 'h', 'a'
    ], $filename);

    // Remove special characters and replace spaces with underscores
    $filename = preg_replace('/[^a-zA-Z0-9\s\-_]/', '', $filename);
    $filename = preg_replace('/\s+/', '_', trim($filename));
    $filename = preg_replace('/_{2,}/', '_', $filename);

    // Limit length
    $filename = substr($filename, 0, 50);

    return $filename ?: 'image';
}
?>




