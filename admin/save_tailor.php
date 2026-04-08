<?php
require_once 'auth_check.php';
require_once '../includes/db_connect.php';
require_once '../includes/cities.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
    $location = isset($_POST['location']) ? trim((string)$_POST['location']) : '';
    if ($location === '__other__') {
        $location = isset($_POST['location_other']) ? trim((string)$_POST['location_other']) : '';
    }

    if (!$pdo) {
        header("Location: index.php");
        exit;
    }

    try {
        silah_get_cities($pdo);
        if ($location !== '') {
            $stmt = $pdo->prepare("INSERT IGNORE INTO cities (name, country, is_active) VALUES (?, 'Pakistan', 1)");
            $stmt->execute([$location]);
        }
    } catch (Exception $e) {
    }
    
    $data = [
        isset($_POST['name']) ? (string)$_POST['name'] : '',
        isset($_POST['tagline']) ? (string)$_POST['tagline'] : '',
        isset($_POST['description']) ? (string)$_POST['description'] : '',
        $location,
        isset($_POST['profile_image']) ? (string)$_POST['profile_image'] : '',
        isset($_POST['price_range_min']) ? (string)$_POST['price_range_min'] : '',
        isset($_POST['price_range_max']) ? (string)$_POST['price_range_max'] : '',
        isset($_POST['experience_years']) ? (string)$_POST['experience_years'] : '',
        isset($_POST['email']) ? (string)$_POST['email'] : '',
        isset($_POST['phone']) ? (string)$_POST['phone'] : ''
    ];
    
    if ($id) {
        $password_part = "";
        if (!empty($_POST['password'])) {
            $password_part = ", password=?";
            $data[] = password_hash($_POST['password'], PASSWORD_DEFAULT);
        }
        $sql = "UPDATE tailors SET name=?, tagline=?, description=?, location=?, profile_image=?, price_range_min=?, price_range_max=?, experience_years=?, email=?, phone=? $password_part WHERE id=?";
        $data[] = $id;
    } else {
        $password = !empty($_POST['password']) ? password_hash($_POST['password'], PASSWORD_DEFAULT) : password_hash('tailor123', PASSWORD_DEFAULT);
        $data[] = $password;
        $sql = "INSERT INTO tailors (name, tagline, description, location, profile_image, price_range_min, price_range_max, experience_years, email, phone, password) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
    }
    
    try {
        $stmt = $pdo->prepare($sql);
        $stmt->execute($data);
        header("Location: index.php");
        exit;
    } catch (PDOException $e) {
        die("Error saving tailor: " . $e->getMessage());
    }
}
?>
