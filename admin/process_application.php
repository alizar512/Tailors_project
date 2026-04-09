<?php
require_once 'auth_check.php';
require_once __DIR__ . '/../includes/db_connect.php';
require_once __DIR__ . '/../includes/notifications.php';
require_once __DIR__ . '/../includes/mailer.php';

if (!$pdo) {
    $_SESSION['error'] = 'Database connection failed.';
    header("Location: applications.php");
    exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['application_id']) && isset($_POST['action'])) {
    $app_id = (int)$_POST['application_id'];
    $action = $_POST['action'];

    if ($action === 'approve') {
        // Fetch application details
        $stmt = $pdo->prepare("SELECT * FROM tailor_applications WHERE id = ?");
        $stmt->execute([$app_id]);
        $app = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($app && $app['status'] !== 'approved') {
            try {
                $pdo->beginTransaction();

                $checkColumnStmt = $pdo->prepare(
                    "SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
                     WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ?"
                );

                $tailorColumnsToEnsure = [
                    'password' => "ALTER TABLE tailors ADD COLUMN password VARCHAR(255)",
                    'password_reset_required' => "ALTER TABLE tailors ADD COLUMN password_reset_required TINYINT(1) NOT NULL DEFAULT 0",
                    'is_active' => "ALTER TABLE tailors ADD COLUMN is_active TINYINT(1) NOT NULL DEFAULT 1",
                    'profile_completed' => "ALTER TABLE tailors ADD COLUMN profile_completed TINYINT(1) NOT NULL DEFAULT 0",
                    'username' => "ALTER TABLE tailors ADD COLUMN username VARCHAR(50) UNIQUE",
                    'address' => "ALTER TABLE tailors ADD COLUMN address TEXT",
                    'skills' => "ALTER TABLE tailors ADD COLUMN skills TEXT",
                    'instagram_link' => "ALTER TABLE tailors ADD COLUMN instagram_link VARCHAR(255)",
                    'price_range_min' => "ALTER TABLE tailors ADD COLUMN price_range_min DECIMAL(10,2)",
                ];

                foreach ($tailorColumnsToEnsure as $columnName => $ddl) {
                    $checkColumnStmt->execute(['tailors', $columnName]);
                    if ((int)$checkColumnStmt->fetchColumn() === 0) {
                        $pdo->exec($ddl);
                    }
                }

                // 1. Update application status
                $updateStmt = $pdo->prepare("UPDATE tailor_applications SET status = 'approved' WHERE id = ?");
                $updateStmt->execute([$app_id]);

                $default_image = 'https://ui-avatars.com/api/?name=' . urlencode($app['name']) . '&background=random';
                $plain_password = bin2hex(random_bytes(4));
                $default_password = password_hash($plain_password, PASSWORD_DEFAULT);
                $profile_image = isset($app['profile_image']) && $app['profile_image'] ? $app['profile_image'] : $default_image;

                $existingTailor = null;
                $existingTailorId = 0;
                if (isset($app['email']) && $app['email']) {
                    $findTailorStmt = $pdo->prepare("SELECT * FROM tailors WHERE email = ? ORDER BY id DESC LIMIT 1");
                    $findTailorStmt->execute([(string)$app['email']]);
                    $existingTailor = $findTailorStmt->fetch(PDO::FETCH_ASSOC);
                    $existingTailorId = $existingTailor ? (int)$existingTailor['id'] : 0;
                }

                $username = $existingTailor && isset($existingTailor['username']) ? trim((string)$existingTailor['username']) : '';
                if ($username === '') {
                    $usernameBase = strtolower(preg_replace('/[^a-zA-Z0-9_]/', '', strtok((string)$app['email'], '@')));
                    if ($usernameBase === '') {
                        $usernameBase = 'tailor';
                    }
                    $username = $usernameBase;
                    $i = 0;
                    $uStmt = $pdo->prepare("SELECT COUNT(*) FROM tailors WHERE username = ? AND id <> ?");
                    while (true) {
                        $uStmt->execute([$username, $existingTailorId]);
                        if ((int)$uStmt->fetchColumn() === 0) {
                            break;
                        }
                        $i++;
                        $username = $usernameBase . $i;
                        if ($i > 1000) {
                            $username = $usernameBase . bin2hex(random_bytes(2));
                            break;
                        }
                    }
                }

                if ($existingTailorId > 0) {
                    $updateTailorStmt = $pdo->prepare(
                        "UPDATE tailors SET
                            name = ?, username = ?, location = ?, address = ?, experience_years = ?,
                            tagline = ?, description = ?, skills = ?, instagram_link = ?, profile_image = ?,
                            email = ?, phone = ?, price_range_min = ?,
                            password = ?, password_reset_required = 1, is_active = 1
                         WHERE id = ?"
                    );
                    $updateTailorStmt->execute([
                        $app['name'],
                        $username,
                        $app['location'],
                        $app['address'],
                        $app['experience_years'],
                        $app['specialization'],
                        "Expert in " . $app['specialization'],
                        $app['specialization'],
                        isset($app['instagram_link']) ? $app['instagram_link'] : null,
                        $profile_image,
                        $app['email'],
                        $app['phone'],
                        isset($app['price_range_min']) ? $app['price_range_min'] : null,
                        $default_password,
                        $existingTailorId
                    ]);
                    $newTailorId = $existingTailorId;
                } else {
                    $insertStmt = $pdo->prepare("INSERT INTO tailors (name, username, location, address, experience_years, tagline, description, skills, instagram_link, profile_image, email, phone, price_range_min, password, password_reset_required, is_active, profile_completed) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 1, 1, 0)");
                    $insertStmt->execute([
                        $app['name'],
                        $username,
                        $app['location'],
                        $app['address'],
                        $app['experience_years'],
                        $app['specialization'], // Use specialization as tagline
                        "Expert in " . $app['specialization'], // Generic description
                        $app['specialization'],
                        isset($app['instagram_link']) ? $app['instagram_link'] : null,
                        $profile_image,
                        $app['email'],
                        $app['phone'],
                        isset($app['price_range_min']) ? $app['price_range_min'] : null,
                        $default_password
                    ]);

                    $newTailorId = (int)$pdo->lastInsertId();
                    if ($newTailorId <= 0) {
                        $tailorIdStmt = $pdo->prepare("SELECT id FROM tailors WHERE email = ? ORDER BY id DESC LIMIT 1");
                        $tailorIdStmt->execute([(string)$app['email']]);
                        $newTailorId = (int)$tailorIdStmt->fetchColumn();
                    }
                }

                $pdo->exec(
                    "CREATE TABLE IF NOT EXISTS portfolio_images (
                        id INT AUTO_INCREMENT PRIMARY KEY,
                        tailor_id INT,
                        image_url VARCHAR(255) NOT NULL,
                        description VARCHAR(255),
                        INDEX idx_portfolio_images_tailor_id (tailor_id)
                    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4"
                );

                $images = json_decode(isset($app['portfolio_link']) ? (string)$app['portfolio_link'] : '', true);
                if (!is_array($images)) {
                    $images = [];
                }
                if ($newTailorId > 0 && !empty($images)) {
                    $imgExistsStmt = $pdo->prepare("SELECT COUNT(*) FROM portfolio_images WHERE tailor_id = ? AND image_url = ?");
                    $imgInsertStmt = $pdo->prepare("INSERT INTO portfolio_images (tailor_id, image_url, description) VALUES (?, ?, ?)");
                    foreach ($images as $imgPath) {
                        $imgPath = is_string($imgPath) ? trim($imgPath) : '';
                        if ($imgPath === '') {
                            continue;
                        }
                        $imgExistsStmt->execute([$newTailorId, $imgPath]);
                        if ((int)$imgExistsStmt->fetchColumn() === 0) {
                            $imgInsertStmt->execute([$newTailorId, $imgPath, null]);
                        }
                    }
                }

                $pdo->exec(
                    "CREATE TABLE IF NOT EXISTS portfolio_videos (
                        id INT AUTO_INCREMENT PRIMARY KEY,
                        tailor_id INT,
                        video_url VARCHAR(255) NOT NULL,
                        description VARCHAR(255),
                        INDEX idx_portfolio_videos_tailor_id (tailor_id)
                    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4"
                );

                $videos = json_decode(isset($app['portfolio_videos']) ? (string)$app['portfolio_videos'] : '', true);
                if (!is_array($videos)) {
                    $videos = [];
                }
                if ($newTailorId > 0 && !empty($videos)) {
                    $vidExistsStmt = $pdo->prepare("SELECT COUNT(*) FROM portfolio_videos WHERE tailor_id = ? AND video_url = ?");
                    $vidInsertStmt = $pdo->prepare("INSERT INTO portfolio_videos (tailor_id, video_url, description) VALUES (?, ?, ?)");
                    foreach ($videos as $vidPath) {
                        $vidPath = is_string($vidPath) ? trim($vidPath) : '';
                        if ($vidPath === '') {
                            continue;
                        }
                        $vidExistsStmt->execute([$newTailorId, $vidPath]);
                        if ((int)$vidExistsStmt->fetchColumn() === 0) {
                            $vidInsertStmt->execute([$newTailorId, $vidPath, null]);
                        }
                    }
                }

                // Add Notification
                silah_add_notification(
                    $pdo,
                    'admin',
                    null,
                    "New Tailor Approved",
                    $app['name'] . " has been approved as a professional tailor.",
                    "tailor",
                    "tailors.php"
                );

                $pdo->commit();

                $_SESSION['success'] = "Tailor approved and added successfully.";
                if (isset($app['email']) && $app['email']) {
                    $baseUrl = 'http://' . $_SERVER['HTTP_HOST'];
                    $loginUrl = $baseUrl . '/admin/login.php';
                    $to = (string)$app['email'];
                    $subject = 'Silah: Your tailor application is approved';
                    $message =
                        "Hi " . (string)$app['name'] . ",\n\n" .
                        "Your application to join Silah as a Tailor has been approved.\n\n" .
                        "Login details:\n" .
                        "Login page: " . $loginUrl . "\n" .
                        "Role: Tailor\n" .
                        "Email: " . $to . "\n" .
                        "Temporary password: " . $plain_password . "\n\n" .
                        "After logging in, please update your password immediately.\n\n" .
                        "Thank you,\n" .
                        "Silah Team\n";
                    $mailSent = silah_send_email($to, $subject, $message);
                    if ($mailSent) {
                        $_SESSION['success'] = "Tailor approved and email sent with login details.";
                    } else {
                        $_SESSION['success'] = "Tailor approved. Could not send email from this server. Login: " . $to . " Temporary password: " . $plain_password;
                    }
                } else {
                    $_SESSION['success'] = "Tailor approved. No email on application; temporary password is " . $plain_password;
                }

            } catch (Exception $e) {
                $pdo->rollBack();
                $_SESSION['error'] = "Error approving tailor: " . $e->getMessage();
            }
        }
    } elseif ($action === 'reject') {
        $stmt = $pdo->prepare("UPDATE tailor_applications SET status = 'rejected' WHERE id = ?");
        $stmt->execute([$app_id]);
        $_SESSION['success'] = "Application rejected.";
    }
}

header("Location: applications.php");
exit();
?>
