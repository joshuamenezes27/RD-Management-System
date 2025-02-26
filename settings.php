<?php
session_start();
include 'db_connection.php'; // Include your database connection
include 'get_user_name.php'; // Include the file to get the logged-in user's name

// Check if the user is logged in
if (!isset($_SESSION['username'])) {
    header("Location: login.php");
    exit();
}

// Feedback messages for form actions
$update_message = '';
$password_message = '';

// Handle profile update
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['update_profile'])) {
    $name = $_POST['name'];
    $birthdate = $_POST['birthdate'];
    $username = $_SESSION['username'];

    $stmt = $pdo->prepare("UPDATE users SET name = ?, birthdate = ? WHERE username = ?");
    if ($stmt->execute([$name, $birthdate, $username])) {
        $update_message = "Profile updated successfully.";
    } else {
        $update_message = "Error updating profile.";
    }
}

// Handle password change
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['change_password'])) {
    $current_password = $_POST['current_password'];
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];

    $stmt = $pdo->prepare("SELECT password FROM users WHERE username = ?");
    $stmt->execute([$_SESSION['username']]);
    $user = $stmt->fetch();

    if ($user && password_verify($current_password, $user['password'])) {
        if ($new_password === $confirm_password) {
            $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
            $update_stmt = $pdo->prepare("UPDATE users SET password = ? WHERE username = ?");
            $update_stmt->execute([$hashed_password, $_SESSION['username']]);
            $password_message = "Password changed successfully.";
        } else {
            $password_message = "New passwords do not match.";
        }
    } else {
        $password_message = "Incorrect current password.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Settings</title>
    <link rel="stylesheet" href="settings.css">
</head>
<body>
    <div class="container">
        <!-- Top Navigation Bar -->
        <div class="navbar">
            <div class="navbar-left">
                <h2>Menezes Enterprises</h2>
            </div>
            <div class="navbar-right">
                <h3><?php echo htmlspecialchars($user_name); ?></h3>
                <ul>
                    <li><a href="dashboard.php">Dashboard</a></li>
                    <li><a href="add_customer.php">Add Customer</a></li>
                    <li><a href="edit_customer.php">Edit</a></li>
                    <li><a href="settings.php">Settings</a></li>
                    <li><a href="logout.php">Logout</a></li>
                </ul>
            </div>
        </div>

        <!-- Main Content Area -->
        <div class="main-content">
            <h2>Settings</h2>

            <!-- Profile Update Section -->
            <h3>Update Profile</h3>
            <?php if (!empty($update_message)): ?>
                <p class="success-message"><?php echo $update_message; ?></p>
            <?php endif; ?>
            <form method="POST" action="">
                <label for="name">Name:</label>
                <input type="text" id="name" name="name" required>

                <label for="birthdate">Birthdate:</label>
                <input type="date" id="birthdate" name="birthdate" required>

                <button type="submit" name="update_profile">Save Changes</button>
            </form>

            <!-- Password Change Section -->
            <h3>Change Password</h3>
            <?php if (!empty($password_message)): ?>
                <p class="success-message"><?php echo $password_message; ?></p>
            <?php endif; ?>
            <form method="POST" action="">
                <label for="current_password">Current Password:</label>
                <input type="password" id="current_password" name="current_password" required>

                <label for="new_password">New Password:</label>
                <input type="password" id="new_password" name="new_password" required>

                <label for="confirm_password">Confirm New Password:</label>
                <input type="password" id="confirm_password" name="confirm_password" required>

                <button type="submit" name="change_password">Change Password</button>
            </form>
        </div>
    </div>
</body>
</html>
