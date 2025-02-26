<?php
// Database connection settings
$host = 'localhost';     // Server host (XAMPP defaults to 'localhost')
$dbname = 'postal_rd'; // Replace with your database name
$username = ''; // Replace with your database username (XAMPP defaults to 'root')
$password = ''; // Replace with your database password (default is an empty string in XAMPP)

// Creating a PDO instance (PHP Data Object) to connect to the database
try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);
    // Set error mode to exception to handle errors more effectively
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}
?>
