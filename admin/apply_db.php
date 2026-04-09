<?php
require_once 'auth_check.php';
require_once __DIR__ . '/../includes/db_connect.php';

$updates = [];

if ($pdo) {
    try {
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
        $updates[] = "tailor_applications ensured";
    } catch (Exception $e) {
    }

    try {
        $pdo->exec(
            "CREATE TABLE IF NOT EXISTS tailors (
                id INT AUTO_INCREMENT PRIMARY KEY,
                name VARCHAR(100) NOT NULL,
                username VARCHAR(50) UNIQUE,
                email VARCHAR(100),
                phone VARCHAR(20),
                location VARCHAR(100),
                address TEXT,
                experience_years INT DEFAULT 0,
                tagline VARCHAR(255),
                skills TEXT,
                instagram_link VARCHAR(255),
                description TEXT,
                profile_image VARCHAR(255),
                price_range_min DECIMAL(10,2),
                password VARCHAR(255),
                password_reset_required TINYINT(1) NOT NULL DEFAULT 0,
                is_active TINYINT(1) NOT NULL DEFAULT 1,
                profile_completed TINYINT(1) NOT NULL DEFAULT 0,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4"
        );
        $updates[] = "tailors ensured";
    } catch (Exception $e) {
    }

    try {
        $stmt = $pdo->query("SELECT COUNT(*) FROM (SELECT LOWER(TRIM(email)) AS e, COUNT(*) c FROM tailors WHERE email IS NOT NULL AND TRIM(email) <> '' GROUP BY LOWER(TRIM(email)) HAVING c > 1) t");
        $dupCount = (int)$stmt->fetchColumn();
        if ($dupCount === 0) {
            $pdo->exec("ALTER TABLE tailors ADD UNIQUE KEY uq_tailors_email (email)");
            $updates[] = "tailors.email unique ensured";
        } else {
            $updates[] = "tailors.email unique skipped (duplicates exist)";
        }
    } catch (Exception $e) {
    }

    try {
        $pdo->exec(
            "CREATE TABLE IF NOT EXISTS portfolio_images (
                id INT AUTO_INCREMENT PRIMARY KEY,
                tailor_id INT,
                image_url VARCHAR(255) NOT NULL,
                description VARCHAR(255),
                FOREIGN KEY (tailor_id) REFERENCES tailors(id) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4"
        );
        $updates[] = "portfolio_images ensured";
    } catch (Exception $e) {
    }

    try {
        $pdo->exec(
            "CREATE TABLE IF NOT EXISTS portfolio_videos (
                id INT AUTO_INCREMENT PRIMARY KEY,
                tailor_id INT,
                video_url VARCHAR(255) NOT NULL,
                description VARCHAR(255),
                INDEX idx_portfolio_videos_tailor_id (tailor_id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4"
        );
        $updates[] = "portfolio_videos ensured";
    } catch (Exception $e) {
    }

    try {
        $pdo->exec(
            "CREATE TABLE IF NOT EXISTS orders (
                id INT AUTO_INCREMENT PRIMARY KEY,
                customer_name VARCHAR(100) NOT NULL,
                customer_email VARCHAR(100) NOT NULL,
                customer_phone VARCHAR(20),
                tailor_id INT,
                preferred_tailors TEXT,
                service_type VARCHAR(100),
                budget DECIMAL(10,2),
                location_details TEXT,
                expected_delivery VARCHAR(50),
                reference_image VARCHAR(255),
                notes TEXT,
                measurements TEXT,
                status VARCHAR(50) DEFAULT 'Order Placed',
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4"
        );
        $updates[] = "orders ensured";
    } catch (Exception $e) {
    }

    $checkColumnStmt = $pdo->prepare(
        "SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
         WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ?"
    );

    $ensureColumns = [
        'tailor_applications' => [
            'price_range_min' => "ALTER TABLE tailor_applications ADD COLUMN price_range_min DECIMAL(10,2)",
            'profile_image' => "ALTER TABLE tailor_applications ADD COLUMN profile_image VARCHAR(255)",
        ],
        'orders' => [
            'preferred_tailors' => "ALTER TABLE orders ADD COLUMN preferred_tailors TEXT",
            'budget' => "ALTER TABLE orders ADD COLUMN budget DECIMAL(10,2)",
            'location_details' => "ALTER TABLE orders ADD COLUMN location_details TEXT",
            'expected_delivery' => "ALTER TABLE orders ADD COLUMN expected_delivery VARCHAR(50)",
            'reference_image' => "ALTER TABLE orders ADD COLUMN reference_image VARCHAR(255)",
            'notes' => "ALTER TABLE orders ADD COLUMN notes TEXT",
            'measurements' => "ALTER TABLE orders ADD COLUMN measurements TEXT",
            'status' => "ALTER TABLE orders ADD COLUMN status VARCHAR(50) DEFAULT 'Order Placed'",
            'created_at' => "ALTER TABLE orders ADD COLUMN created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP",
        ],
    ];

    foreach ($ensureColumns as $table => $cols) {
        foreach ($cols as $col => $ddl) {
            try {
                $checkColumnStmt->execute([$table, $col]);
                if ((int)$checkColumnStmt->fetchColumn() === 0) {
                    $pdo->exec($ddl);
                    $updates[] = "$table.$col added";
                }
            } catch (Exception $e) {
            }
        }
    }
}

include 'header.php';
include 'sidebar.php';
?>

<div class="glass-card p-10 max-w-4xl mx-auto">
    <div class="mb-8">
        <h2 class="text-2xl font-black text-primary mb-1">Apply Database Updates</h2>
        <p class="text-xs text-gray-500 font-medium mb-0">Ensures all required tables and columns exist.</p>
    </div>

    <?php if (!$pdo): ?>
        <div class="p-4 rounded-2xl border bg-red-50 border-red-100">
            <p class="text-sm font-semibold text-red-800 mb-0">Database connection failed.</p>
        </div>
    <?php else: ?>
        <div class="mb-6 p-4 rounded-2xl border bg-green-50 border-green-100">
            <p class="text-xs font-extrabold text-green-700 uppercase tracking-widest mb-1">Completed</p>
            <p class="text-sm font-semibold text-green-800 mb-0">Database schema updates applied.</p>
        </div>

        <ul class="list-group">
            <?php foreach ($updates as $u): ?>
                <li class="list-group-item"><?= htmlspecialchars((string)$u) ?></li>
            <?php endforeach; ?>
            <?php if (empty($updates)): ?>
                <li class="list-group-item text-gray-500">No changes needed.</li>
            <?php endif; ?>
        </ul>
    <?php endif; ?>
</div>

<?php include 'footer.php'; ?>
