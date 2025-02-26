<?php
include 'db_connection.php';  // Include your database connection file
include 'get_user_name.php';
// Function to delete customer and associated payments
function deleteCustomer($acc_no) {
    global $pdo;  // Use the PDO instance from db_connection.php

    try {
        // Start a transaction
        $pdo->beginTransaction();

        // First, delete the payments associated with the customer
        $deletePayments = "DELETE FROM payments WHERE acc_no = :acc_no";
        $stmt = $pdo->prepare($deletePayments);
        $stmt->bindParam(':acc_no', $acc_no, PDO::PARAM_STR);
        $stmt->execute();

        // Now, delete the customer from the customers table
        $deleteCustomer = "DELETE FROM customers WHERE acc_no = :acc_no";
        $stmt = $pdo->prepare($deleteCustomer);
        $stmt->bindParam(':acc_no', $acc_no, PDO::PARAM_STR);
        $stmt->execute();

        // Commit the transaction
        $pdo->commit();
        echo "Customer and associated payments deleted successfully.";
    } catch (Exception $e) {
        // If there's an error, roll back the transaction
        $pdo->rollBack();
        echo "Failed to delete customer: " . $e->getMessage();
    }
}

// Example usage (assuming you're passing the `acc_no` of the customer to be deleted)
if (isset($_GET['acc_no'])) {
    $acc_no = $_GET['acc_no'];  // Get the account number from the URL or form
    deleteCustomer($acc_no);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="deletecuss.css">
    <title>Delete Customer</title>
</head>
<body>
    <div class="container">
    <!-- Navigation Bar -->
    <div class="navbar">
        <div class="navbar-left">
            <h2>Menezes Enterprises</h2>
        </div>
        <div class="navbar-right">
            <h3><?php echo htmlspecialchars($user_name); ?></h3> <!-- Display logged-in user's name -->
            <ul>
                <li><a href="dashboard.php">Dashboard</a></li>
                <li><a href="add_customer.php">Add Customer</a></li>
                <li><a href="edit_customer.php">Edit</a></li>
                <li><a href="settings.php">Settings</a></li>
                <li><a href="logout.php">Logout</a></li> <!-- Logout link -->
                <li><a href="deletecus.php">Delete</a></li>
            </ul>
        </div>
    </div>



    <h2>Delete Customer</h2>
    <form method="get" action="">
        <label for="acc_no">Enter Customer Account Number to Delete:</label>
        <input type="text" id="acc_no" name="acc_no" required>
        <button type="submit">Delete</button>
    </form>
</body>
</html>

    
