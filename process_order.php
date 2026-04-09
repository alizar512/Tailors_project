<?php
require_once __DIR__ . '/includes/db_connect.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = isset($_POST['name']) ? trim((string)$_POST['name']) : '';
    $email_raw = isset($_POST['email']) ? trim((string)$_POST['email']) : '';
    $email = filter_var($email_raw, FILTER_VALIDATE_EMAIL) ? $email_raw : '';
    $phone = isset($_POST['phone']) ? trim((string)$_POST['phone']) : '';
    $service_type = isset($_POST['service_type']) ? trim((string)$_POST['service_type']) : '';
    $budget = isset($_POST['budget']) && is_numeric($_POST['budget']) ? (float)$_POST['budget'] : null;
    $is_own_clothing = isset($_POST['is_own_clothing']) ? (int)$_POST['is_own_clothing'] : 0;
    $location_details = isset($_POST['location_details']) ? trim((string)$_POST['location_details']) : '';
    $expected_delivery = isset($_POST['expected_delivery']) ? trim((string)$_POST['expected_delivery']) : '';
    $notes = isset($_POST['notes']) ? trim((string)$_POST['notes']) : '';
    $measurements = isset($_POST['measurements']) ? trim((string)$_POST['measurements']) : '';
    $hire_tailor_id = isset($_POST['hire_tailor_id']) && is_numeric($_POST['hire_tailor_id']) ? (int)$_POST['hire_tailor_id'] : 0;
    $selected_tailor_id = isset($_POST['selected_tailor_id']) && is_numeric($_POST['selected_tailor_id']) ? (int)$_POST['selected_tailor_id'] : 0;

    $service_ids = [];
    if (isset($_POST['service_ids']) && is_array($_POST['service_ids'])) {
        foreach ($_POST['service_ids'] as $sid) {
            if (is_numeric($sid)) {
                $service_ids[] = (int)$sid;
            }
        }
        $service_ids = array_values(array_unique(array_filter($service_ids)));
    }

    $preferred_tailors_arr = [];
    if (isset($_POST['preferred_tailors']) && is_array($_POST['preferred_tailors'])) {
        foreach ($_POST['preferred_tailors'] as $id) {
            if (is_numeric($id)) {
                $preferred_tailors_arr[] = (int)$id;
            }
        }
    }
    if ($hire_tailor_id > 0 && !in_array($hire_tailor_id, $preferred_tailors_arr, true)) {
        $preferred_tailors_arr[] = $hire_tailor_id;
    }
    $preferred_tailors = !empty($preferred_tailors_arr) ? json_encode(array_values(array_unique($preferred_tailors_arr))) : null;

    // Mandatory notes for "Other" service
    if ($service_type === 'Other' && empty($notes)) {
        header("Location: index.php?error=notes_required");
        exit;
    }

    // Handle File Upload
    $reference_image = '';
    if (isset($_FILES['reference_image']) && $_FILES['reference_image']['error'] === UPLOAD_ERR_OK) {
        $fileType = $_FILES['reference_image']['type'];
        $allowedTypes = ['image/jpeg', 'image/png', 'image/webp', 'image/gif'];
        
        if (in_array($fileType, $allowedTypes)) {
            $uploadDir = 'uploads/';
            if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);
            
            $fileName = uniqid() . '_' . basename($_FILES['reference_image']['name']);
            $uploadPath = $uploadDir . $fileName;
            
            if (move_uploaded_file($_FILES['reference_image']['tmp_name'], $uploadPath)) {
                $reference_image = $uploadPath;
            }
        }
    }

    // Insert into Database
    if ($pdo) {
        try {
            $pdo->exec(
                "CREATE TABLE IF NOT EXISTS orders (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    order_number VARCHAR(20),
                    customer_name VARCHAR(100) NOT NULL,
                    customer_email VARCHAR(100) NOT NULL,
                    customer_phone VARCHAR(20),
                    tailor_id INT,
                    is_own_clothing TINYINT(1) DEFAULT 0,
                    preferred_tailors TEXT,
                    service_type VARCHAR(100),
                    service_items TEXT,
                    budget DECIMAL(10,2),
                    total_price DECIMAL(10,2),
                    advance_payment_amount DECIMAL(10,2),
                    payment_status VARCHAR(30) DEFAULT 'Pending',
                    payment_proof_image VARCHAR(255),
                    payment_submitted_at TIMESTAMP NULL,
                    payment_confirmed_at TIMESTAMP NULL,
                    location_details TEXT,
                    expected_delivery VARCHAR(50),
                    reference_image VARCHAR(255),
                    notes TEXT,
                    measurements TEXT,
                    chat_token VARCHAR(64),
                    tailor_offer_price DECIMAL(10,2),
                    tailor_offer_notes TEXT,
                    status VARCHAR(50) DEFAULT 'Order Placed',
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4"
            );

            $checkColumnStmt = $pdo->prepare(
                "SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
                 WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ?"
            );
            $cols = [
                'order_number' => "ALTER TABLE orders ADD COLUMN order_number VARCHAR(20)",
                'tailor_id' => "ALTER TABLE orders ADD COLUMN tailor_id INT",
                'is_own_clothing' => "ALTER TABLE orders ADD COLUMN is_own_clothing TINYINT(1) DEFAULT 0",
                'preferred_tailors' => "ALTER TABLE orders ADD COLUMN preferred_tailors TEXT",
                'budget' => "ALTER TABLE orders ADD COLUMN budget DECIMAL(10,2)",
                'total_price' => "ALTER TABLE orders ADD COLUMN total_price DECIMAL(10,2)",
                'advance_payment_amount' => "ALTER TABLE orders ADD COLUMN advance_payment_amount DECIMAL(10,2)",
                'payment_status' => "ALTER TABLE orders ADD COLUMN payment_status VARCHAR(30) DEFAULT 'Pending'",
                'payment_proof_image' => "ALTER TABLE orders ADD COLUMN payment_proof_image VARCHAR(255)",
                'payment_submitted_at' => "ALTER TABLE orders ADD COLUMN payment_submitted_at TIMESTAMP NULL",
                'payment_confirmed_at' => "ALTER TABLE orders ADD COLUMN payment_confirmed_at TIMESTAMP NULL",
                'location_details' => "ALTER TABLE orders ADD COLUMN location_details TEXT",
                'expected_delivery' => "ALTER TABLE orders ADD COLUMN expected_delivery VARCHAR(50)",
                'reference_image' => "ALTER TABLE orders ADD COLUMN reference_image VARCHAR(255)",
                'notes' => "ALTER TABLE orders ADD COLUMN notes TEXT",
                'measurements' => "ALTER TABLE orders ADD COLUMN measurements TEXT",
                'chat_token' => "ALTER TABLE orders ADD COLUMN chat_token VARCHAR(64)",
                'tailor_offer_price' => "ALTER TABLE orders ADD COLUMN tailor_offer_price DECIMAL(10,2)",
                'tailor_offer_notes' => "ALTER TABLE orders ADD COLUMN tailor_offer_notes TEXT",
                'service_items' => "ALTER TABLE orders ADD COLUMN service_items TEXT",
                'status' => "ALTER TABLE orders ADD COLUMN status VARCHAR(50) DEFAULT 'Order Placed'",
                'created_at' => "ALTER TABLE orders ADD COLUMN created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP",
            ];
            foreach ($cols as $col => $ddl) {
                $checkColumnStmt->execute(['orders', $col]);
                if ((int)$checkColumnStmt->fetchColumn() === 0) {
                    $pdo->exec($ddl);
                }
            }

            $tailor_id = $hire_tailor_id > 0 ? $hire_tailor_id : ($selected_tailor_id > 0 ? $selected_tailor_id : null);
            $service_items_json = null;

            if ($tailor_id !== null && !empty($service_ids)) {
                try {
                    $pdo->exec(
                        "CREATE TABLE IF NOT EXISTS tailor_services (
                            id INT AUTO_INCREMENT PRIMARY KEY,
                            tailor_id INT NOT NULL,
                            service_name VARCHAR(120) NOT NULL,
                            price DECIMAL(10,2) NOT NULL DEFAULT 0,
                            is_active TINYINT(1) NOT NULL DEFAULT 1,
                            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                            INDEX idx_tailor_services_tailor_id (tailor_id),
                            INDEX idx_tailor_services_active (is_active)
                        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4"
                    );
                } catch (Exception $e) {
                }

                $placeholders = implode(',', array_fill(0, count($service_ids), '?'));
                $params = array_merge([(int)$tailor_id], $service_ids);
                $stmt = $pdo->prepare("SELECT id, service_name, price FROM tailor_services WHERE tailor_id = ? AND is_active = 1 AND id IN ($placeholders)");
                $stmt->execute($params);
                $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

                $map = [];
                foreach ($rows as $r) {
                    $map[(int)$r['id']] = $r;
                }

                $items = [];
                $total = 0.0;
                foreach ($service_ids as $sid) {
                    if (!isset($map[$sid])) {
                        continue;
                    }
                    $row = $map[$sid];
                    $price = isset($row['price']) ? (float)$row['price'] : 0.0;
                    $items[] = ['id' => (int)$row['id'], 'name' => (string)$row['service_name'], 'price' => $price];
                    $total += $price;
                }

                if (!empty($items)) {
                    $budget = $total;
                    $names = [];
                    foreach ($items as $it) {
                        $names[] = (string)$it['name'];
                    }
                    $service_type = implode(', ', $names);
                    if (strlen($service_type) > 100) {
                        $service_type = substr($service_type, 0, 97) . '...';
                    }
                    $service_items_json = json_encode($items);
                }
            }

            $status = $tailor_id !== null ? 'Tailor Selected' : 'Order Placed';
            $stmt = $pdo->prepare("INSERT INTO orders (customer_name, customer_email, customer_phone, tailor_id, is_own_clothing, preferred_tailors, service_type, service_items, budget, location_details, expected_delivery, reference_image, notes, measurements, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([$name, $email, $phone, $tailor_id, $is_own_clothing, $preferred_tailors, $service_type, $service_items_json, $budget, $location_details, $expected_delivery, $reference_image, $notes, $measurements !== '' ? $measurements : null, $status]);
            $orderId = (int)$pdo->lastInsertId();

            if ($orderId > 0) {
                $orderNumber = 'SIL-' . str_pad((string)$orderId, 4, '0', STR_PAD_LEFT);
                try {
                    $stmt = $pdo->prepare("UPDATE orders SET order_number = ? WHERE id = ? AND (order_number IS NULL OR TRIM(order_number) = '')");
                    $stmt->execute([$orderNumber, $orderId]);
                } catch (Exception $e) {
                }
            }

            $chatToken = bin2hex(random_bytes(16));
            if ($orderId > 0) {
                try {
                    $stmt = $pdo->prepare("UPDATE orders SET chat_token = ? WHERE id = ?");
                    $stmt->execute([$chatToken, $orderId]);
                } catch (Exception $e) {
                }
            }

            try {
                $pdo->exec(
                    "CREATE TABLE IF NOT EXISTS order_messages (
                        id INT AUTO_INCREMENT PRIMARY KEY,
                        order_id INT NOT NULL,
                        sender_type VARCHAR(20) NOT NULL,
                        sender_name VARCHAR(100),
                        sender_email VARCHAR(120),
                        message TEXT NOT NULL,
                        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                        INDEX idx_order_messages_order_id (order_id)
                    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4"
                );
                if ($orderId > 0) {
                    $msg =
                        "New order request.\n" .
                        "Services: " . ($service_type !== '' ? $service_type : '-') . "\n" .
                        "Total: " . ($budget !== null ? ('PKR ' . number_format((float)$budget)) : '-') . "\n" .
                        "Own clothing: " . ($is_own_clothing ? 'Yes' : 'No') . "\n" .
                        "Delivery: " . ($expected_delivery !== '' ? $expected_delivery : '-') . "\n\n" .
                        "Notes:\n" . ($notes !== '' ? $notes : '-') . "\n\n" .
                        "Measurements:\n" . ($measurements !== '' ? $measurements : '-') . "\n";

                    $ins = $pdo->prepare("INSERT INTO order_messages (order_id, sender_type, sender_name, sender_email, message) VALUES (?, 'customer', ?, ?, ?)");
                    $ins->execute([$orderId, $name, $email, $msg]);
                }
            } catch (Exception $e) {
            }

            if ($tailor_id !== null && (int)$tailor_id > 0) {
                try {
                    $pdo->exec(
                        "CREATE TABLE IF NOT EXISTS tailor_messages (
                            id INT AUTO_INCREMENT PRIMARY KEY,
                            tailor_id INT NOT NULL,
                            order_id INT,
                            customer_name VARCHAR(100) NOT NULL,
                            customer_email VARCHAR(100) NOT NULL,
                            customer_phone VARCHAR(30),
                            customer_address TEXT,
                            message TEXT NOT NULL,
                            is_read TINYINT(1) NOT NULL DEFAULT 0,
                            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                            INDEX idx_tailor_messages_tailor_id (tailor_id),
                            INDEX idx_tailor_messages_order_id (order_id)
                        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4"
                    );
                    try {
                        $pdo->exec("ALTER TABLE tailor_messages ADD COLUMN order_id INT");
                    } catch (Exception $e) {
                    }

                    $msg =
                        "New order request.\n" .
                        "Order #: " . ($orderId > 0 ? (string)$orderId : '-') . "\n" .
                        "Services: " . ($service_type !== '' ? $service_type : '-') . "\n" .
                        "Total: " . ($budget !== null ? ('PKR ' . number_format((float)$budget)) : '-') . "\n" .
                        "Own clothing: " . ($is_own_clothing ? 'Yes' : 'No') . "\n" .
                        "Delivery: " . ($expected_delivery !== '' ? $expected_delivery : '-') . "\n\n" .
                        "Notes:\n" . ($notes !== '' ? $notes : '-') . "\n\n" .
                        "Measurements:\n" . ($measurements !== '' ? $measurements : '-') . "\n";

                    $ins = $pdo->prepare("INSERT INTO tailor_messages (tailor_id, order_id, customer_name, customer_email, customer_phone, customer_address, message) VALUES (?, ?, ?, ?, ?, ?, ?)");
                    $ins->execute([(int)$tailor_id, (int)$orderId, $name, $email, $phone, $location_details, $msg]);
                } catch (Exception $e) {
                }
            }
        } catch (PDOException $e) {
            error_log($e->getMessage());
        }
    }

    // Send Email (Simulated)
    $to = "silah.orders@gmail.com";
    $subject = "New Order Request from $name";
    $message = "Name: $name\nEmail: $email\nPhone: $phone\nService: $service_type\nOwn Clothing: " . ($is_own_clothing ? 'Yes' : 'No') . "\nPreferred Tailors: $preferred_tailors\nBudget: $budget PKR\nDelivery: $expected_delivery\nLocation: $location_details\nNotes: $notes\nMeasurements: " . ($measurements !== '' ? $measurements : '-') ;
    $headers = "From: silah.orders@gmail.com";
    
    // mail($to, $subject, $message, $headers); // Uncomment on live server

    if (isset($chatToken) && $chatToken !== '') {
        $returnUrl = isset($_POST['return_url']) ? trim((string)$_POST['return_url']) : '';
        $isSafeReturn = $returnUrl !== '' &&
            strpos($returnUrl, '://') === false &&
            strpos($returnUrl, "\n") === false &&
            strpos($returnUrl, "\r") === false &&
            strpos($returnUrl, '..') === false &&
            preg_match('/^[a-zA-Z0-9_\\-\\/\\.]+(\\?.*)?$/', $returnUrl);
        if (!$isSafeReturn) {
            $returnUrl = $hire_tailor_id > 0 ? ("tailor_profile.php?id=" . $hire_tailor_id) : "place_order.php";
        }
        header("Location: order_submitted.php?token=" . urlencode($chatToken) . "&return=" . urlencode($returnUrl));
        exit;
    }

    if ($hire_tailor_id > 0) {
        header("Location: tailor_profile.php?id=" . $hire_tailor_id . "&status=order_success");
        exit;
    }

    header("Location: index.php?status=order_success");
    exit;
} else {
    header("Location: index.php");
    exit;
}
?>
