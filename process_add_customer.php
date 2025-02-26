<?php
session_start();
include 'db_connection.php'; // Include your database connection

// Process the form data
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Collect the form data
    $name = $_POST['name'];
    $phone = $_POST['phone'];
    $acc_no = $_POST['acc_no'];
    $amount = $_POST['amount'];
    $plan_type = $_POST['plan_type'];
    $due_date = $_POST['due_date']; // Due date (Before/After 15th)
    $date_started = $_POST['date_started']; // RD Start Date
    $paid_till_date = $_POST['paid_till_date']; // Last Amount Paid

    try {
        // Start a transaction
        $pdo->beginTransaction();

        // Check if the account number already exists in the customers table
        $stmt_check = $pdo->prepare("SELECT COUNT(*) FROM customers WHERE acc_no = ?");
        $stmt_check->execute([$acc_no]);
        $customerExists = $stmt_check->fetchColumn();

        if ($customerExists) {
            // Update customer details if they exist
            $stmt_update_customer = $pdo->prepare("UPDATE customers 
                                                   SET name = ?, phone = ?, amount = ?, plan_type = ?, date_started = ?, paid_till_date = ?, due_date = ? 
                                                   WHERE acc_no = ?");
            $stmt_update_customer->execute([$name, $phone, $amount, $plan_type, $date_started, $paid_till_date, $due_date, $acc_no]);
        } else {
            // Insert new customer data
            $stmt_customer = $pdo->prepare("INSERT INTO customers (acc_no, name, phone, amount, plan_type, date_started, paid_till_date, due_date) 
                                           VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt_customer->execute([$acc_no, $name, $phone, $amount, $plan_type, $date_started, $paid_till_date, $due_date]);
        }

        // Convert dates into DateTime objects for easier comparison and iteration
        $startDate = new DateTime($date_started);
        $endDate = new DateTime($paid_till_date);
        $isYearlyPlan = ($plan_type === 'Yearly'); // Check plan type

        // Loop through each month (or year for yearly plans) from the start date to the paid till date
        $currentDate = clone $startDate;
        while ($currentDate <= $endDate) {
            $month = $currentDate->format('m');
            $year = $currentDate->format('Y');

            // Calculate the due date
            if ($due_date == 'Before 15th') {
                $due_date_obj = new DateTime("$year-$month-15");
            } else {
                $lastDay = cal_days_in_month(CAL_GREGORIAN, $month, $year);
                $due_date_obj = new DateTime("$year-$month-$lastDay");
            }
            $due_date_str = $due_date_obj->format('Y-m-d');

            // Check if a payment record for this month/year already exists
            $stmt_check_payment = $pdo->prepare("SELECT * FROM payments WHERE acc_no = ? AND payment_month = ? AND payment_year = ?");
            $stmt_check_payment->execute([$acc_no, $month, $year]);
            $paymentExists = $stmt_check_payment->fetch();

            if ($paymentExists) {
                // Update the payment record
                $stmt_update_payment = $pdo->prepare("UPDATE payments 
                                                     SET payment_status = 'Paid', payment_date = ?, due_date = ? 
                                                     WHERE acc_no = ? AND payment_month = ? AND payment_year = ?");
                $stmt_update_payment->execute([$due_date_str, $due_date_str, $acc_no, $month, $year]);
            } else {
                // Insert a new payment record
                $stmt_insert_payment = $pdo->prepare("INSERT INTO payments (acc_no, payment_month, payment_year, payment_status, payment_date, due_date) 
                                                     VALUES (?, ?, ?, ?, ?, ?)");
                $stmt_insert_payment->execute([$acc_no, $month, $year, 'Paid', $due_date_str, $due_date_str]);
            }

            // Increment date
            if ($isYearlyPlan) {
                $currentDate->modify('+1 year'); // Yearly plan: increment by a year
            } else {
                $currentDate->modify('first day of next month'); // Monthly plan: increment by a month
            }
        }

        // Insert pending record for the next payment period
        $paidTillDate = new DateTime($paid_till_date);
        $nextPeriod = clone $paidTillDate;

        if ($isYearlyPlan) {
            $nextPeriod->modify('+1 year'); // Next payment year for yearly plans
        } else {
            $nextPeriod->modify('first day of next month'); // Next payment month for monthly plans
        }

        $nextMonth = $nextPeriod->format('m');
        $nextYear = $nextPeriod->format('Y');

        // Check if the next payment record exists
        $stmt_check_next_period = $pdo->prepare("SELECT COUNT(*) FROM payments WHERE acc_no = ? AND payment_month = ? AND payment_year = ?");
        $stmt_check_next_period->execute([$acc_no, $nextMonth, $nextYear]);
        $nextPeriodExists = $stmt_check_next_period->fetchColumn();

        if (!$nextPeriodExists) {
            // Calculate the due date for the next period
            if ($due_date == 'Before 15th') {
                $next_due_date_obj = new DateTime("$nextYear-$nextMonth-15");
            } else {
                $lastDay = cal_days_in_month(CAL_GREGORIAN, $nextMonth, $nextYear);
                $next_due_date_obj = new DateTime("$nextYear-$nextMonth-$lastDay");
            }
            $next_due_date_str = $next_due_date_obj->format('Y-m-d');

            // Insert pending record for the next period
            $stmt_insert_pending = $pdo->prepare("INSERT INTO payments (acc_no, payment_month, payment_year, payment_status, payment_date, due_date) 
                                                 VALUES (?, ?, ?, ?, NULL, ?)");
            $stmt_insert_pending->execute([$acc_no, $nextMonth, $nextYear, 'Pending', $next_due_date_str]);
        }

        // Commit the transaction
        $pdo->commit();

        $_SESSION['success'] = "Customer and payment details added successfully!";
        header("Location: add_customer.php");
        exit();

    } catch (PDOException $e) {
        $pdo->rollBack();
        echo "Error adding customer and payment details: " . $e->getMessage();
    }
}
?>
