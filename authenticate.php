<?php
session_start();
require 'db_connection.php'; // Ensure this is the correct path

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = $_POST['username'];
    $password = $_POST['password'];

    // Fetch the user from the database
    $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ?");
    $stmt->execute([$username]);
    $user = $stmt->fetch();

    if ($user && password_verify($password, $user['password'])) {
        // Successful login
        $_SESSION['username'] = $username; // Store the username in session
        header("Location: dashboard.php"); // Redirect to dashboard
        exit();
    } else {
        // Set error message in session and redirect back to login page
        $_SESSION['error_message'] = "Invalid username or password.";
        header("Location: login.php"); // Adjust the path if needed
        exit();
    }
}
?>
