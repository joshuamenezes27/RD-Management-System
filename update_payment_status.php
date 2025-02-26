<?php
include 'db_connection.php'; // Include your database connection

// Get current date
$currentDate = new DateTime();

// Fetch customers and their payment details
$sql = "SELECT c.acc_no, c.plan_type, p.payment_time, p.payment_month, p.due_date, p.payment_status, p.advance_paid
        FROM customers c
        LEFT JOIN payments p ON c.acc_no = p.acc_no
        WHERE p.payment_status = 'Pending' OR p.payment_status = 'Paid'";

$result = $pdo->query($sql); // Use PDO query

while ($row = $result->fetch(PDO::FETCH_ASSOC)) { // Use PDO fetch
    // For monthly plans, set the due date based on payment_time (Before 15th / After 15th)
    if ($row['plan_type'] == 'Monthly') {
        // Determine the due date
        $dueDate = new DateTime($row['payment_month']);

        if ($row['payment_time'] == 'Before 15th') {
            // Set to 15th of the current month or next month
            $dueDate->setDate($dueDate->format('Y'), $dueDate->format('m'), 15);
        } else {
            // Set to the last day of the current month
            $dueDate->modify('last day of this month');
        }

        // Check if payment is overdue (if current date > due date)
        if ($currentDate > $dueDate) {
            // Reset to unpaid if payment is overdue
            $updatePaymentStatus = $pdo->prepare("UPDATE payments 
                                                  SET payment_status = 'Pending', 
                                                      last_payment_date = NULL 
                                                  WHERE acc_no = :acc_no AND payment_month = :payment_month");
            $updatePaymentStatus->bindParam(':acc_no', $row['acc_no']);
            $updatePaymentStatus->bindParam(':payment_month', $row['payment_month']);

            if ($updatePaymentStatus->execute()) {
                // Optionally log that the status was updated
                // echo "Payment status updated to Pending for Account No: " . $row['acc_no'];
            } else {
                // Log any errors that happen while updating
                echo "Error updating payment status for Account No: " . $row['acc_no'];
            }
        }
    }

    // If advance amount was paid, adjust for the next month's payment
    if ($row['advance_paid'] > 0) {
        // Logic to adjust for the next month's payment
        // You can calculate the adjustment by deducting the advance amount from the next month's due payment
        $adjustmentQuery = $pdo->prepare("UPDATE payments 
                                           SET advance_paid = advance_paid - :advance_paid 
                                           WHERE acc_no = :acc_no AND payment_status = 'Pending'");
        $adjustmentQuery->bindParam(':advance_paid', $row['advance_paid']);
        $adjustmentQuery->bindParam(':acc_no', $row['acc_no']);
        
        if ($adjustmentQuery->execute()) {
            // Optionally log the adjustment made
            // echo "Advance payment deducted for Account No: " . $row['acc_no'];
        } else {
            // Log any errors related to the adjustment
            echo "Error adjusting payment for Account No: " . $row['acc_no'];
        }
    }
}

// Close connection
$pdo = null;
?>
