<?php
// Include db_connection.php at the start of this file
include 'db_connection.php';

// Ensure the PDO connection is accessible
global $pdo;

// Initialize the variable
$user_name = '';

// Check if session has the username and the connection is set
if (isset($_SESSION['username']) && isset($pdo)) {
    $username = $_SESSION['username'];

    // Prepare and execute the query using PDO
    $stmt = $pdo->prepare("SELECT name FROM users WHERE username = :username");
    $stmt->bindParam(':username', $username);
    $stmt->execute();

    // Fetch the result
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($user) {
        $user_name = $user['name'];
    }
}
?>
