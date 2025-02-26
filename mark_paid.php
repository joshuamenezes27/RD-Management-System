<?php
// Include the database connection
require_once('db_connection.php');

// Retrieve the account number and payment month from the URL parameters
$acc_no = $_GET['acc_no'];
$payment_month = $_GET['payment_month'];

// Update the payment status to 'Paid' for the given customer and month
$query = "UPDATE payments SET payment_status = 'Paid' WHERE acc_no = :acc_no AND payment_month = :payment_month";
$stmt = $pdo->prepare($query);
$stmt->bindParam(':acc_no', $acc_no);
$stmt->bindParam(':payment_month', $payment_month);

if ($stmt->execute()) {
    // Redirect back to the dashboard after marking as paid
    header("Location: dashboard.php");
    exit();
} else {
    echo "Error updating payment status.";
}
?>
