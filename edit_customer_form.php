<?php
session_start();
require 'db_connection.php'; // Ensure this path is correct
include 'get_user_name.php'; // Include the file to get the logged-in user's name

// Check if the user is logged in
if (!isset($_SESSION['username'])) {
    header("Location: login.php");
    exit();
}

// Initialize variables
$customer = [];
$message = '';

// Check if `acc_no` is set in the URL
if (isset($_GET['acc_no'])) {
    $acc_no = $_GET['acc_no'];

    // Fetch customer details based on `acc_no`
    $stmt = $pdo->prepare("SELECT * FROM customers WHERE acc_no = ?");
    $stmt->execute([$acc_no]);
    $customer = $stmt->fetch();

    if (!$customer) {
        $message = "Customer not found.";
    }
}

// Handle form submission to update customer details
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Get updated customer details from the form
    $name = $_POST['name'];
    $phone = $_POST['phone'];
    $amount = $_POST['amount'];
    $plan_type = $_POST['plan_type'];
    $due_date = $_POST['due_date'];
    $date_started = $_POST['date_started'];

    // Update the customer in the database
    $update_stmt = $pdo->prepare(
        "UPDATE customers SET name = ?, phone = ?, amount = ?, plan_type = ?, due_date = ?, date_started = ? WHERE acc_no = ?"
    );
    if ($update_stmt->execute([$name, $phone, $amount, $plan_type, $due_date, $date_started, $acc_no])) {
        $message = "Customer details updated successfully.";
    } else {
        $message = "Failed to update customer details.";
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

    <div class="main-content">
        <h2>Edit Customer Details</h2>

        <?php if ($message): ?>
            <p class="error-message"><?php echo htmlspecialchars($message); ?></p>
        <?php endif; ?>

        <?php if ($customer): ?>
            <form method="POST" action="">
                <label for="name">Name:</label>
                <input type="text" id="name" name="name" value="<?php echo htmlspecialchars($customer['name']); ?>" required>

                <label for="phone">Phone:</label>
                <input type="tel" id="phone" name="phone" value="<?php echo htmlspecialchars($customer['phone']); ?>" required>

                <label for="amount">Amount to be Paid:</label>
                <input type="number" id="amount" name="amount" value="<?php echo htmlspecialchars($customer['amount']); ?>" required>

                <label for="plan_type">Plan Type:</label>
                <select id="plan_type" name="plan_type" required>
                    <option value="Monthly" <?php if ($customer['plan_type'] == 'Monthly') echo 'selected'; ?>>Monthly</option>
                    <option value="Yearly" <?php if ($customer['plan_type'] == 'Yearly') echo 'selected'; ?>>Yearly</option>
                </select>

                <label for="due_date">Due Date:</label>
                <select id="due_date" name="due_date" required>
                    <option value="Before 15th" <?php if ($customer['due_date'] == 'Before 15th') echo 'selected'; ?>>Before 15th</option>
                    <option value="After 15th" <?php if ($customer['due_date'] == 'After 15th') echo 'selected'; ?>>After 15th</option>
                </select>

                <label for="date_started">Date Started:</label>
                <input type="date" id="date_started" name="date_started" value="<?php echo htmlspecialchars($customer['date_started']); ?>" required>

                <input type="submit" value="Update Customer">
            </form>
        <?php else: ?>
            <p>Customer not found. Please return to the customer list.</p>
        <?php endif; ?>
    </div>
</div>
</body>
</html>
