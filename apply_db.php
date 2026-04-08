<?php
$username = 'root';
$password = '';
$dbname = 'tailor_db';

try {
    $pdo = null;
    $hosts = ['127.0.0.1', 'localhost'];
    $ports = [3306, 3307];

    foreach ($hosts as $h) {
        foreach ($ports as $p) {
            try {
                $pdo = new PDO("mysql:host=$h;port=$p;charset=utf8", $username, $password, [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                ]);
                break 2;
            } catch (PDOException $e) {
                $pdo = null;
            }
        }
    }

    if (!$pdo) {
        throw new PDOException("Could not connect to MySQL on localhost (ports 3306/3307). Start MySQL in XAMPP.");
    }

    $pdo->exec("CREATE DATABASE IF NOT EXISTS $dbname");
    $pdo->exec("USE $dbname");
    
    $sql = file_get_contents('db/update_schema.sql');
    
    // Split SQL by semicolon to execute statements individually if needed, 
    // but exec() can handle multiple statements in some drivers. 
    // Safest is to just run it.
    $pdo->exec($sql);
    
    echo "Database updated successfully.";
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?>
