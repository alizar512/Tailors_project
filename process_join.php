<?php
require_once __DIR__ . '/includes/db_connect.php';
require_once __DIR__ . '/includes/cities.php';
require_once __DIR__ . '/includes/schema_utils.php';

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
    $portfolio_required = $instagram_link === '';

    $profile_image_url = isset($_POST['profile_image_url']) ? trim((string)$_POST['profile_image_url']) : '';
    $profile_image_url = $profile_image_url !== '' && filter_var($profile_image_url, FILTER_VALIDATE_URL) ? $profile_image_url : '';
    $portfolio_image_urls = isset($_POST['portfolio_image_urls']) && is_array($_POST['portfolio_image_urls']) ? $_POST['portfolio_image_urls'] : [];
    $portfolio_image_urls = array_values(array_filter(array_map(function($u) {
        $u = trim((string)$u);
        return $u !== '' && filter_var($u, FILTER_VALIDATE_URL) ? $u : '';
    }, $portfolio_image_urls)));

    $portfolio_video_urls = isset($_POST['portfolio_video_urls']) && is_array($_POST['portfolio_video_urls']) ? $_POST['portfolio_video_urls'] : [];
    $portfolio_video_urls = array_values(array_filter(array_map(function($u) {
        $u = trim((string)$u);
        return $u !== '' && filter_var($u, FILTER_VALIDATE_URL) ? $u : '';
    }, $portfolio_video_urls)));

    // Handle Media Uploads
    $profile_image = null;
    $profile_blob = null;
    $profile_mime = null;
    $portfolio_images = [];
    $portfolio_videos = [];
    $isServerless = getenv('VERCEL') === '1' || getenv('AWS_LAMBDA_FUNCTION_NAME');

    if (isset($_FILES['profile_image']) && $_FILES['profile_image']['error'] === UPLOAD_ERR_OK) {
        $fileType = isset($_FILES['profile_image']['type']) ? (string)$_FILES['profile_image']['type'] : '';
        $fileSize = isset($_FILES['profile_image']['size']) ? (int)$_FILES['profile_image']['size'] : 0;
        $isImage = $fileType !== '' ? (stripos($fileType, 'image/') === 0) : true;
        if ($isImage) {
            if ($isServerless) {
                if ($fileSize > 0 && $fileSize <= 6000000) {
                    $bytes = @file_get_contents($_FILES['profile_image']['tmp_name']);
                    if ($bytes !== false && $bytes !== '') {
                        $outBytes = $bytes;
                        $outMime = $fileType;
                        $maxBytes = 800000;
                        if (function_exists('imagecreatefromstring') && function_exists('imagejpeg') && function_exists('imagesx') && function_exists('imagesy')) {
                            try {
                                $im = @imagecreatefromstring($bytes);
                                if ($im) {
                                    $w = imagesx($im);
                                    $h = imagesy($im);
                                    $max = 720;
                                    $scale = ($w > 0 && $h > 0) ? min(1, $max / max($w, $h)) : 1;
                                    $nw = (int)max(1, floor($w * $scale));
                                    $nh = (int)max(1, floor($h * $scale));
                                    $dst = $im;
                                    if ($scale < 1 && function_exists('imagecreatetruecolor')) {
                                        $tmp = imagecreatetruecolor($nw, $nh);
                                        if ($tmp) {
                                            imagecopyresampled($tmp, $im, 0, 0, 0, 0, $nw, $nh, $w, $h);
                                            $dst = $tmp;
                                        }
                                    }
                                    $qualities = [82, 75, 68, 60];
                                    foreach ($qualities as $q) {
                                        ob_start();
                                        imagejpeg($dst, null, $q);
                                        $jpeg = ob_get_clean();
                                        if (is_string($jpeg) && $jpeg !== '' && strlen($jpeg) <= $maxBytes) {
                                            $outBytes = $jpeg;
                                            $outMime = 'image/jpeg';
                                            break;
                                        }
                                        if (is_string($jpeg) && $jpeg !== '') {
                                            $outBytes = $jpeg;
                                            $outMime = 'image/jpeg';
                                        }
                                    }
                                    if ($dst !== $im) {
                                        imagedestroy($dst);
                                    }
                                    imagedestroy($im);
                                }
                            } catch (Exception $e) {
                            }
                        }
                        if (strlen($outBytes) <= $maxBytes) {
                            $profile_blob = $outBytes;
                            $profile_mime = $outMime !== '' ? $outMime : 'image/jpeg';
                            $profile_image = '';
                        }
                    }
                }
            } else {
                $uploadDir = 'uploads/profile/';
                if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);

                $fileName = uniqid() . '_' . basename($_FILES['profile_image']['name']);
                $uploadPath = $uploadDir . $fileName;
                if (move_uploaded_file($_FILES['profile_image']['tmp_name'], $uploadPath)) {
                    $profile_image = $uploadPath;
                }
            }
        }
    }

    if ($profile_image_url !== '') {
        $profile_image = $profile_image_url;
        $profile_blob = null;
        $profile_mime = null;
    }

    if (isset($_FILES['portfolio_images'])) {
        $uploadDir = 'uploads/portfolio/';
        if (!$isServerless && !is_dir($uploadDir)) mkdir($uploadDir, 0777, true);
        
        foreach ($_FILES['portfolio_images']['tmp_name'] as $key => $tmpName) {
            if ($_FILES['portfolio_images']['error'][$key] === UPLOAD_ERR_OK) {
                if (!$isServerless) {
                    $fileName = uniqid() . '_' . basename($_FILES['portfolio_images']['name'][$key]);
                    $uploadPath = $uploadDir . $fileName;
                    if (move_uploaded_file($tmpName, $uploadPath)) {
                        $portfolio_images[] = $uploadPath;
                    }
                } else {
                    $type = (string)($_FILES['portfolio_images']['type'][$key] ?? '');
                    if ($type !== '' && stripos($type, 'image/') !== 0) {
                        continue;
                    }
                    $portfolio_images[] = [
                        'tmp' => (string)$tmpName,
                        'type' => $type,
                        'size' => (int)($_FILES['portfolio_images']['size'][$key] ?? 0),
                    ];
                }
            }
        }
    }

    if (isset($_FILES['portfolio_videos'])) {
        if ($isServerless) {
            $portfolio_videos = [];
        } else {
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
    }

    if (!empty($portfolio_video_urls)) {
        $portfolio_videos = $portfolio_video_urls;
    }

    if (!empty($portfolio_image_urls)) {
        $portfolio_images_json = json_encode($portfolio_image_urls);
        $portfolio_images = $portfolio_image_urls;
    } else {
        $portfolio_images_json = json_encode($isServerless ? [] : $portfolio_images);
    }
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
                profile_image_blob LONGBLOB NULL,
                profile_image_mime VARCHAR(100) NULL,
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

        silah_ensure_table($pdo,
            "CREATE TABLE IF NOT EXISTS tailor_application_files (
                id INT AUTO_INCREMENT PRIMARY KEY,
                application_id INT NOT NULL,
                file_kind VARCHAR(30) NOT NULL,
                mime VARCHAR(100),
                blob LONGBLOB,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                INDEX idx_app_files_application_id (application_id)
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
            'profile_image_blob' => "ALTER TABLE tailor_applications ADD COLUMN profile_image_blob LONGBLOB NULL",
            'profile_image_mime' => "ALTER TABLE tailor_applications ADD COLUMN profile_image_mime VARCHAR(100) NULL",
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

        $stmt = $pdo->prepare("INSERT INTO tailor_applications (name, email, profile_image, profile_image_blob, profile_image_mime, phone, location, address, experience_years, specialization, price_range_min, instagram_link, portfolio_link, portfolio_videos) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$name, $email, $profile_image, $profile_blob, $profile_mime, $phone, $location, $address, $experience_years, $specialization, $price_range_min, $instagram_link, $portfolio_images_json, $portfolio_videos_json]);
        $appId = (int)$pdo->lastInsertId();

        $savedPortfolioIds = [];
        if ($isServerless && $appId > 0 && is_array($portfolio_images) && !empty($portfolio_images)) {
            $insert = $pdo->prepare("INSERT INTO tailor_application_files (application_id, file_kind, mime, blob) VALUES (?, 'portfolio_image', ?, ?)");
            $fileIds = [];
            foreach ($portfolio_images as $f) {
                if (!is_array($f)) continue;
                $mime = isset($f['type']) ? (string)$f['type'] : '';
                $size = isset($f['size']) ? (int)$f['size'] : 0;
                if ($size <= 0 || $size > 6000000) continue;
                $tmp = isset($f['tmp']) ? (string)$f['tmp'] : '';
                if ($tmp === '') continue;
                $bytes = @file_get_contents($tmp);
                if ($bytes === false || $bytes === '') continue;

                $outBytes = $bytes;
                $outMime = $mime;
                if (function_exists('imagecreatefromstring') && function_exists('imagejpeg') && function_exists('imagesx') && function_exists('imagesy')) {
                    try {
                        $im = @imagecreatefromstring($bytes);
                        if ($im) {
                            $w = imagesx($im);
                            $h = imagesy($im);
                            $max = 900;
                            $scale = ($w > 0 && $h > 0) ? min(1, $max / max($w, $h)) : 1;
                            $nw = (int)max(1, floor($w * $scale));
                            $nh = (int)max(1, floor($h * $scale));
                            $dst = $im;
                            if ($scale < 1 && function_exists('imagecreatetruecolor')) {
                                $tmp2 = imagecreatetruecolor($nw, $nh);
                                if ($tmp2) {
                                    imagecopyresampled($tmp2, $im, 0, 0, 0, 0, $nw, $nh, $w, $h);
                                    $dst = $tmp2;
                                }
                            }
                            $maxBytes = 1200000;
                            $qualities = [82, 75, 68, 60];
                            foreach ($qualities as $q) {
                                ob_start();
                                imagejpeg($dst, null, $q);
                                $jpeg = ob_get_clean();
                                if (is_string($jpeg) && $jpeg !== '' && strlen($jpeg) <= $maxBytes) {
                                    $outBytes = $jpeg;
                                    $outMime = 'image/jpeg';
                                    break;
                                }
                                if (is_string($jpeg) && $jpeg !== '') {
                                    $outBytes = $jpeg;
                                    $outMime = 'image/jpeg';
                                }
                            }
                            if ($dst !== $im) {
                                imagedestroy($dst);
                            }
                            imagedestroy($im);
                        }
                    } catch (Exception $e) {
                    }
                }
                if (strlen($outBytes) > 1200000) continue;
                try {
                    $insert->execute([$appId, $outMime, $outBytes]);
                    $fid = (int)$pdo->lastInsertId();
                    if ($fid > 0) $fileIds[] = $fid;
                } catch (Exception $e) {
                }
            }
            if (!empty($fileIds)) {
                $savedPortfolioIds = $fileIds;
                try {
                    $stmt = $pdo->prepare("UPDATE tailor_applications SET portfolio_link = ? WHERE id = ?");
                    $stmt->execute([json_encode($fileIds), $appId]);
                } catch (Exception $e) {
                }
            }
        }

        if ($portfolio_required) {
            $hasAny = $isServerless ? (!empty($portfolio_image_urls) || !empty($savedPortfolioIds)) : !empty($portfolio_images);
            if (!$hasAny) {
                try {
                    $stmt = $pdo->prepare("DELETE FROM tailor_applications WHERE id = ? LIMIT 1");
                    $stmt->execute([$appId]);
                } catch (Exception $e) {
                }
                $err = $isServerless
                    ? "Portfolio image was not received. Please ensure Cloudinary is configured and upload JPG/PNG."
                    : "Portfolio image was not received. Please upload JPG/PNG and keep each image under 1.2MB.";
                header("Location: join_tailor.php?status=error&err=" . urlencode($err));
                exit;
            }
        }
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
