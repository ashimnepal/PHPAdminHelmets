<?php
// dbinit.php

$host = 'localhost';   // Adjust as needed
$dbname = 'Helmets'; // Database name
$username = 'root';     // Database username
$password = '';         // Database password

// Create connection
try {
    $pdo = new PDO("mysql:host=$host", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Create the database if it doesn't exist
    $pdo->exec("CREATE DATABASE IF NOT EXISTS $dbname");
    $pdo->exec("USE $dbname");

    // Create table
    $createTableSql = "CREATE TABLE IF NOT EXISTS products (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(255) NOT NULL,
        price DECIMAL(10, 2) NOT NULL,
        productAddedBy VARCHAR(100) NOT NULL DEFAULT 'Ashim'
    )";
    $pdo->exec($createTableSql);

    echo "Database and table created successfully.";
} catch (PDOException $e) {
    die("DB ERROR: ". $e->getMessage());
}
?>
