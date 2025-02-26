<?php
session_start();
require 'db_connection.php'; // Ensure this path is correct
include 'get_user_name.php'; // Include the file to get the logged-in user's name

// Check if the user is logged in
if (!isset($_SESSION['username'])) {
    header("Location: login.php");
    exit();
}

// Variables to store feedback messages
$customer_details = [];
$message = '';

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['search'])) {
    $name = $_POST['name'];

    // Execute query to find customers by name
    $query = "SELECT * FROM customers WHERE name LIKE ?";
    $stmt = $pdo->prepare($query);
    $stmt->execute(['%' . $name . '%']); // Use LIKE for partial matches
    $customer_details = $stmt->fetchAll();

    if (empty($customer_details)) {
        $message = "No customer found with the provided name.";
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Edit Customer</title>
    <link rel="stylesheet" href="editt.css">
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
                <li><a href="logout.php">Logout</a></li> <!-- Add logout if needed -->
            </ul>
        </div>
    </div>

    <div class="main-content">
        <h2>Edit Customer</h2>

        <form method="POST" action="">
            <label for="name">Name:</label>
            <input type="text" id="name" name="name" placeholder="Enter Name" required>
            <button type="submit" name="search">Search</button>
        </form>

        <?php if ($message): ?>
            <p class="error-message"><?php echo $message; ?></p>
        <?php endif; ?>

        <?php if (!empty($customer_details)): ?>
            <h3>Customer Details</h3>
            <table>
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Phone</th>
                        <th>Acc No</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($customer_details as $customer): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($customer['name']); ?></td>
                            <td><?php echo htmlspecialchars($customer['phone']); ?></td>
                            <td><?php echo htmlspecialchars($customer['acc_no']); ?></td>
                            <td>
                                <a href="edit_customer_form.php?acc_no=<?php echo htmlspecialchars($customer['acc_no']); ?>">Edit</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
</div>
</body>
</html>
