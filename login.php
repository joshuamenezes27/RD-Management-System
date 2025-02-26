<?php
session_start();
if (isset($_SESSION['username'])) {
    header("Location: dashboard.php"); // Redirect to the main page if already logged in
    exit();
}

$message = '';
if (isset($_SESSION['error_message'])) {
    $message = $_SESSION['error_message'];
    unset($_SESSION['error_message']); // Clear the message after displaying it
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Login</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
<div class="login-container">
    <h2>Login</h2>

    <?php if (!empty($message)): ?>
        <p class="error-message" style="color: red; text-align: center;"><?php echo $message; ?></p>
    <?php endif; ?>

    <form action="authenticate.php" method="POST">
        <table>
            <tr>
                <td><label for="username">Username:</label></td>
                <td><input type="text" id="username" name="username" required></td>
            </tr>
            <tr>
                <td><label for="password">Password:</label></td>
                <td><input type="password" id="password" name="password" required></td>
            </tr>
            <tr>
                <td colspan="2">
                    <center><button type="submit">Login</button></center>
                </td>
            </tr>
        <!--    <tr>
                <td colspan="2">
                    <center><p class="signup-link">New user? <a href="signup.php">Sign up here</a></p></center>
                </td>
            </tr>-->
        </table>
    </form>
</div>
</body>
</html>
