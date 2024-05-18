<?php
require_once('config.php');
// Attempt to connect to the database
try {
    // Create a new PDO instance
    $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME, DB_USER, DB_PASS);

    // Set PDO error mode to exception
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

} catch (PDOException $e) {
    // Display error message
    echo "فشل الاتصال بقاعدة البيانات: " . $e->getMessage();
}
