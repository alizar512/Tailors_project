<?php
require_once 'includes/db_connect.php';
require_once 'includes/cities.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (empty($_POST) && empty($_FILES)) {
        $uploadMax = ini_get('upload_max_filesize');
        $postMax = ini_get('post_max_size');
        $err = "Nothing was received by the server. This usually happens when your upload is too large. Reduce portfolio files or increase PHP limits (upload_max_filesize=$uploadMax, post_max_size=$postMax).";
        header("Location: join_tailor.php?status=error&err=" . urlencode($err));
        exit;
    }

    $name = isset($_POST['name']) ? trim((string)$_POST['name']) : '';
    $email_raw = isset($_POST['email']) ? trim((string)$_POST['email']) : '';
    $email = filter_var($email_raw, FILTER_VALIDATE_EMAIL) ? $email_raw : '';
    $phone = isset($_POST['phone']) ? trim((string)$_POST['phone']) : '';
    $location = isset($_POST['location']) ? trim((string)$_POST['location']) : '';
    if ($location === '__other__') {
        $location = isset($_POST['location_other']) ? trim((string)$_POST['location_other']) : '';
    }
    $address = isset($_POST['address']) ? trim((string)$_POST['address']) : '';
    $experience_years = isset($_POST['experience_years']) && is_numeric($_POST['experience_years']) ? (int)$_POST['experience_years'] : null;
    $specialization = isset($_POST['specialization']) ? trim((string)$_POST['specialization']) : '';
    $price_range_min = isset($_POST['price_range_min']) && is_numeric($_POST['price_range_min']) ? (float)$_POST['price_range_min'] : null;
    $instagram_link_raw = isset($_POST['instagram_link']) ? trim((string)$_POST['instagram_link']) : '';
    $instagram_link = $instagram_link_raw !== '' && filter_var($instagram_link_raw, FILTER_VALIDATE_URL) ? $instagram_link_raw : '';

    // Handle Media Uploads
    $profile_image = null;
    $portfolio_images = [];
    $portfolio_videos = [];

    if (isset($_FILES['profile_image']) && $_FILES['profile_image']['error'] === UPLOAD_ERR_OK) {
        $fileType = isset($_FILES['profile_image']['type']) ? (string)$_FILES['profile_image']['type'] : '';
        $allowedTypes = ['image/jpeg', 'image/png', 'image/webp', 'image/gif'];
        if (in_array($fileType, $allowedTypes, true)) {
            $uploadDir = 'uploads/profile/';
            if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);

            $fileName = uniqid() . '_' . basename($_FILES['profile_image']['name']);
            $uploadPath = $uploadDir . $fileName;
            if (move_uploaded_file($_FILES['profile_image']['tmp_name'], $uploadPath)) {
                $profile_image = $uploadPath;
            }
        }
    }

    if (isset($_FILES['portfolio_images'])) {
        $uploadDir = 'uploads/portfolio/';
        if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);
        
        foreach ($_FILES['portfolio_images']['tmp_name'] as $key => $tmpName) {
            if ($_FILES['portfolio_images']['error'][$key] === UPLOAD_ERR_OK) {
                $fileName = uniqid() . '_' . basename($_FILES['portfolio_images']['name'][$key]);
                $uploadPath = $uploadDir . $fileName;
                if (move_uploaded_file($tmpName, $uploadPath)) {
                    $portfolio_images[] = $uploadPath;
                }
            }
        }
    }

    if (isset($_FILES['portfolio_videos'])) {
        $uploadDir = 'uploads/portfolio/';
        if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);
        
        foreach ($_FILES['portfolio_videos']['tmp_name'] as $key => $tmpName) {
            if ($_FILES['portfolio_videos']['error'][$key] === UPLOAD_ERR_OK) {
                $fileName = uniqid() . '_' . basename($_FILES['portfolio_videos']['name'][$key]);
                $uploadPath = $uploadDir . $fileName;
                if (move_uploaded_file($tmpName, $uploadPath)) {
                    $portfolio_videos[] = $uploadPath;
                }
            }
        }
    }

    $portfolio_images_json = json_encode($portfolio_images);
    $portfolio_videos_json = json_encode($portfolio_videos);

    if ($name === '' || $email === '' || $location === '' || $address === '' || $price_range_min === null) {
        $missing = [];
        if ($name === '') $missing[] = 'name';
        if ($email === '') $missing[] = 'email';
        if ($location === '') $missing[] = 'location';
        if ($address === '') $missing[] = 'address';
        if ($price_range_min === null) $missing[] = 'price_range_min';

        $receivedKeys = implode(', ', array_keys($_POST));
        $debug = "Missing: " . implode(', ', $missing) . ". Received keys: " . ($receivedKeys !== '' ? $receivedKeys : '(none)') . ". name_len=" . strlen($name) . ", email_valid=" . ($email !== '' ? 'yes' : 'no') . ", address_len=" . strlen($address) . ".";
        header("Location: join_tailor.php?status=error&err=" . urlencode($debug));
        exit;
    }

    if (!$pdo) {
        header("Location: join_tailor.php?status=db_error");
        exit;
    }

    try {
        try {
            $cities = silah_get_cities($pdo);
            if ($location !== '') {
                $stmt = $pdo->prepare("INSERT IGNORE INTO cities (name, country, is_active) VALUES (?, 'Pakistan', 1)");
                $stmt->execute([$location]);
            }
        } catch (Exception $e) {
        }

        $pdo->exec(
            "CREATE TABLE IF NOT EXISTS tailor_applications (
                id INT AUTO_INCREMENT PRIMARY KEY,
                name VARCHAR(100) NOT NULL,
                email VARCHAR(100) NOT NULL,
                profile_image VARCHAR(255),
                phone VARCHAR(20),
                location VARCHAR(100),
                address TEXT NOT NULL,
                experience_years INT,
                specialization VARCHAR(255),
                price_range_min DECIMAL(10,2),
                instagram_link VARCHAR(255),
                portfolio_link TEXT,
                portfolio_videos TEXT,
                status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4"
        );

        $checkColumnStmt = $pdo->prepare(
            "SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
             WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ?"
        );

        $columnsToEnsure = [
            'name' => "ALTER TABLE tailor_applications ADD COLUMN name VARCHAR(100) NOT NULL",
            'email' => "ALTER TABLE tailor_applications ADD COLUMN email VARCHAR(100) NOT NULL",
            'profile_image' => "ALTER TABLE tailor_applications ADD COLUMN profile_image VARCHAR(255)",
            'phone' => "ALTER TABLE tailor_applications ADD COLUMN phone VARCHAR(20)",
            'location' => "ALTER TABLE tailor_applications ADD COLUMN location VARCHAR(100)",
            'address' => "ALTER TABLE tailor_applications ADD COLUMN address TEXT",
            'experience_years' => "ALTER TABLE tailor_applications ADD COLUMN experience_years INT",
            'specialization' => "ALTER TABLE tailor_applications ADD COLUMN specialization VARCHAR(255)",
            'price_range_min' => "ALTER TABLE tailor_applications ADD COLUMN price_range_min DECIMAL(10,2)",
            'instagram_link' => "ALTER TABLE tailor_applications ADD COLUMN instagram_link VARCHAR(255)",
            'portfolio_link' => "ALTER TABLE tailor_applications ADD COLUMN portfolio_link TEXT",
            'portfolio_videos' => "ALTER TABLE tailor_applications ADD COLUMN portfolio_videos TEXT",
            'status' => "ALTER TABLE tailor_applications ADD COLUMN status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending'",
            'created_at' => "ALTER TABLE tailor_applications ADD COLUMN created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP",
        ];

        foreach ($columnsToEnsure as $columnName => $ddl) {
            $checkColumnStmt->execute(['tailor_applications', $columnName]);
            if ((int)$checkColumnStmt->fetchColumn() === 0) {
                $pdo->exec($ddl);
            }
        }

        $dupMsg = "This email is already register please try another email";
        $normEmail = strtolower(trim($email));
        $emailExists = 0;
        try {
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM tailors WHERE REPLACE(LOWER(TRIM(email)), ' ', '') = REPLACE(LOWER(TRIM(?)), ' ', '')");
            $stmt->execute([$normEmail]);
            $emailExists += (int)$stmt->fetchColumn();
        } catch (Exception $e) {
        }
        try {
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM tailor_applications WHERE REPLACE(LOWER(TRIM(email)), ' ', '') = REPLACE(LOWER(TRIM(?)), ' ', '')");
            $stmt->execute([$normEmail]);
            $emailExists += (int)$stmt->fetchColumn();
        } catch (Exception $e) {
        }
        if ($emailExists > 0) {
            header("Location: join_tailor.php?status=error&err=" . urlencode($dupMsg));
            exit;
        }

        $stmt = $pdo->prepare("INSERT INTO tailor_applications (name, email, profile_image, phone, location, address, experience_years, specialization, price_range_min, instagram_link, portfolio_link, portfolio_videos) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$name, $email, $profile_image, $phone, $location, $address, $experience_years, $specialization, $price_range_min, $instagram_link, $portfolio_images_json, $portfolio_videos_json]);
        header("Location: join_tailor.php?status=success");
        exit;
    } catch (PDOException $e) {
        $msg = $e->getMessage();
        error_log("Tailor application submit failed: " . $msg);
        $msg = substr($msg, 0, 200);
        header("Location: join_tailor.php?status=error&err=" . urlencode($msg));
        exit;
    }
} else {
    // Redirect back if accessed directly
    header("Location: join_tailor.php");
    exit;
}
?>
