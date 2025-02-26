<?php
session_start();
include 'db_connection.php'; // Include PDO database connection
include 'get_user_name.php'; // Retrieve the user's name

// Check if the user is logged in
if (!isset($_SESSION['username'])) {
    header("Location: login.php"); // Redirect to login page if not logged in
    exit();
}

// Initialize filter variables for unpaid customers
$payment_month_filter = isset($_GET['payment_month']) ? $_GET['payment_month'] : date('m');
$payment_year_filter = isset($_GET['payment_year']) ? $_GET['payment_year'] : date('Y');

// Initialize filter variables for paid customers (these were causing the warnings)
$payment_month_paid_filter = isset($_GET['payment_month_paid']) ? $_GET['payment_month_paid'] : date('m');
$payment_year_paid_filter = isset($_GET['payment_year_paid']) ? $_GET['payment_year_paid'] : date('Y');
$plan_filter_paid = isset($_GET['plan_type_paid']) ? $_GET['plan_type_paid'] : ''; // Default to an empty string

// Default values for unpaid customer filters (already initialized above)
$start_of_month = date('Y-m-d', strtotime("$payment_year_filter-$payment_month_filter-01"));

// Prepare SQL Query for Unpaid Customers
$query_unpaid = "
SELECT c.acc_no, 
       c.name, 
       c.phone, 
       c.amount, 
       c.plan_type, 
       c.date_started, 
       p.payment_status, 
       p.payment_month, 
       p.payment_year, 
       c.due_date AS customer_due_date
FROM customers c
LEFT JOIN payments p 
ON c.acc_no = p.acc_no 
   AND p.payment_month = :payment_month 
   AND p.payment_year = :payment_year
WHERE (p.payment_status = 'pending' OR p.payment_status IS NULL)
  AND (YEAR(c.date_started) < :payment_year 
       OR (YEAR(c.date_started) = :payment_year AND MONTH(c.date_started) <= :payment_month))
ORDER BY FIELD(c.due_date, 'Before 15th', 'After 15th') ASC,c.name ASC;;
";

// Prepare the statement
$stmt_unpaid = $pdo->prepare($query_unpaid);

// Bind parameters
$stmt_unpaid->bindParam(':payment_month', $payment_month_filter, PDO::PARAM_INT);
$stmt_unpaid->bindParam(':payment_year', $payment_year_filter, PDO::PARAM_INT);

// Execute the query
$stmt_unpaid->execute();
$unpaid_customers = $stmt_unpaid->fetchAll();

// Close the cursor after the first query
$stmt_unpaid->closeCursor();

// Fetch paid customer data with due_date
$query_paid = "SELECT c.acc_no, c.name, c.amount, c.plan_type, c.date_started, 
                      c.due_date, p.payment_status, p.payment_date 
               FROM customers c 
               LEFT JOIN payments p 
               ON c.acc_no = p.acc_no 
               WHERE p.payment_status = 'Paid'";

// Further filters for paid customers
if ($payment_month_paid_filter) {
    $query_paid .= " AND MONTH(p.payment_date) = :payment_month_paid";
}
if ($payment_year_paid_filter) {
    $query_paid .= " AND YEAR(p.payment_date) = :payment_year_paid";
}

if ($plan_filter_paid) {
    $query_paid .= " AND c.plan_type = :plan_type_paid";
}

// Sort by due_date
$query_paid .= " ORDER BY c.due_date,c.name";

// Prepare the statement
$stmt_paid = $pdo->prepare($query_paid);

if ($payment_month_paid_filter) {
    $stmt_paid->bindParam(':payment_month_paid', $payment_month_paid_filter);
}
if ($payment_year_paid_filter) {
    $stmt_paid->bindParam(':payment_year_paid', $payment_year_paid_filter);
}
if ($plan_filter_paid) {
    $stmt_paid->bindParam(':plan_type_paid', $plan_filter_paid);
}

$stmt_paid->execute();
$paid_customers = $stmt_paid->fetchAll();


// Handle marking payment as paid
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['mark_paid'])) {
    $acc_no = $_POST['acc_no'];
    $payment_date = $_POST['payment_date'];
    $payment_month = date('m', strtotime($payment_date));
    $payment_year = date('Y', strtotime($payment_date));

    // Check if payment exists for the specified month and year
    $checkQuery = "SELECT payment_id FROM payments 
                   WHERE acc_no = :acc_no AND payment_month = :payment_month AND payment_year = :payment_year AND payment_status = 'Pending'";
    $checkStmt = $pdo->prepare($checkQuery);
    $checkStmt->execute([
        ':acc_no' => $acc_no,
        ':payment_month' => $payment_month,
        ':payment_year' => $payment_year,
    ]);

    if ($checkStmt->rowCount() > 0) {
        // Update existing payment
        $updateQuery = "UPDATE payments 
                        SET payment_status = 'Paid', payment_date = :payment_date 
                        WHERE acc_no = :acc_no AND payment_month = :payment_month AND payment_year = :payment_year";
        $stmt = $pdo->prepare($updateQuery);
    } else {
        // Insert new payment
        $updateQuery = "INSERT INTO payments (acc_no, payment_month, payment_year, payment_status, payment_date) 
                        VALUES (:acc_no, :payment_month, :payment_year, 'Paid', :payment_date)";
        $stmt = $pdo->prepare($updateQuery);
    }

    $stmt->execute([
        ':acc_no' => $acc_no,
        ':payment_month' => $payment_month,
        ':payment_year' => $payment_year,
        ':payment_date' => $payment_date,
    ]);

    // Add a new row with payment_status as 'Pending' and calculate the due date
    $planQuery = "SELECT plan_type FROM customers WHERE acc_no = :acc_no";
    $planStmt = $pdo->prepare($planQuery);
    $planStmt->execute([':acc_no' => $acc_no]);
    $customer = $planStmt->fetch();

    if ($customer) {
        $plan_type = $customer['plan_type'];
        $due_date = ($plan_type === 'Monthly')
            ? date('Y-m-d', strtotime("+1 month", strtotime($payment_date)))
            : date('Y-m-d', strtotime("+1 year", strtotime($payment_date)));

        $insertPendingQuery = "INSERT INTO payments (acc_no, payment_month, payment_year, payment_status, due_date) 
                               VALUES (:acc_no, MONTH(:due_date), YEAR(:due_date), 'Pending', :due_date)";
        $pendingStmt = $pdo->prepare($insertPendingQuery);
        $pendingStmt->execute([
            ':acc_no' => $acc_no,
            ':due_date' => $due_date,
        ]);
    }

    // Redirect back to dashboard
    header("Location: dashboard.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard</title>
    <link rel="stylesheet" href="sett.css"> <!-- Include your CSS file -->
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

    <!-- Filter Section for Unpaid Customers -->
    <div class="filters">
        <form method="GET" action="">
            <h2>Unpaid Customers</h2>
            <label for="payment_month">Month:</label>
            <select name="payment_month" id="payment_month">
                <option value="">All</option>
                <?php
                for ($m = 1; $m <= 12; $m++) {
                    $month_value = str_pad($m, 2, '0', STR_PAD_LEFT);
                    $selected = ($payment_month_filter == $month_value) ? 'selected' : '';
                    echo "<option value=\"$month_value\" $selected>" . date('F', mktime(0, 0, 0, $m, 1)) . "</option>";
                }
                ?>
            </select>

            <label for="payment_year">Year:</label>
            <select name="payment_year" id="payment_year">
                <?php
                $current_year = date('Y');
                for ($y = $current_year - 5; $y <= $current_year + 5; $y++) {
                    $selected = ($payment_year_filter == $y) ? 'selected' : '';
                    echo "<option value=\"$y\" $selected>$y</option>";
                }
                ?>
            </select>

            <button type="submit">Apply Filters</button>
        </form>
    

    <!-- Unpaid Customers Table -->
   
    <table border="1">
        <thead>
            <tr>
                <th>Account No</th>
        <th>Name</th>
        <th>Phone</th>
        <th>Amount</th>
        <th>Plan Type</th>
        <th>Date Started</th>
        <th>Payment Status</th>
        <th>Due Date</th> <!-- New column for due date -->
        <th>Action</th>
    </tr>
</thead>
<tbody>
    <?php if ($unpaid_customers): ?>
        <?php foreach ($unpaid_customers as $customer): ?>
            <tr>
                <td><?php echo htmlspecialchars($customer['acc_no']); ?></td>
                <td><?php echo htmlspecialchars($customer['name']); ?></td>
                <td><?php echo htmlspecialchars($customer['phone']); ?></td>
                <td><?php echo htmlspecialchars($customer['amount']); ?></td>
                <td><?php echo htmlspecialchars($customer['plan_type']); ?></td>
                <td><?php echo htmlspecialchars($customer['date_started']); ?></td>
                <td><?php echo htmlspecialchars($customer['payment_status']); ?></td>
                <td><?php echo htmlspecialchars($customer['customer_due_date']); ?></td> <!-- Display due date -->
                <td>
                    <form method="POST" action="">
                        <input type="hidden" name="acc_no" value="<?php echo htmlspecialchars($customer['acc_no']); ?>">
                        <label for="payment_date">Payment Date:</label>
                        <input type="date" name="payment_date" required>
                        <button type="submit" name="mark_paid">Mark as Paid</button>
                    </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr>
                    <td colspan="8">No unpaid customers found.</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>
    <!-- Filter Section for Paid Customers -->
    <div class="filters">
        <form method="GET" action="">
            <h2>Paid Customers</h2>
            <label for="plan_type_paid">Plan Type:</label>
            <select name="plan_type_paid" id="plan_type_paid">
                <option value="">All</option>
                <option value="Monthly">Monthly</option>
                <option value="Yearly">Yearly</option>
            </select>

            <label for="payment_month_paid">Month:</label>
            <select name="payment_month_paid" id="payment_month_paid">
                <option value="">All</option>
                <?php
                for ($m = 1; $m <= 12; $m++) {
                    $month_value = str_pad($m, 2, '0', STR_PAD_LEFT);
                    $selected = ($payment_month_paid_filter == $month_value) ? 'selected' : '';
                    echo "<option value=\"$month_value\" $selected>" . date('F', mktime(0, 0, 0, $m, 1)) . "</option>";
                }
                ?>
            </select>

            <label for="payment_year_paid">Year:</label>
            <select name="payment_year_paid" id="payment_year_paid">
                <?php
                for ($y = $current_year - 5; $y <= $current_year + 5; $y++) {
                    $selected = ($payment_year_paid_filter == $y) ? 'selected' : '';
                    echo "<option value=\"$y\" $selected>$y</option>";
                }
                ?>
            </select>

            <button type="submit">Apply Filters</button>
        </form>
    </div>

    <!-- Paid Customers Table -->

<table border="1">
    <thead>
        <tr>
            <th>Account No</th>
            <th>Name</th>
            <th>Amount</th>
            <th>Plan Type</th>
            <th>Payment Status</th>
            <th>Payment Month</th>
            <th>Payment Date</th>
            <th>Due Date</th> <!-- Added due_date column -->
        </tr>
    </thead>
    <tbody>
        <?php if ($paid_customers): ?>
            <?php foreach ($paid_customers as $customer): ?>
                <tr>
                    <td><?php echo htmlspecialchars($customer['acc_no']); ?></td>
                    <td><?php echo htmlspecialchars($customer['name']); ?></td>
                    <td><?php echo htmlspecialchars($customer['amount']); ?></td>
                    <td><?php echo htmlspecialchars($customer['plan_type']); ?></td>
                    <td><?php echo htmlspecialchars($customer['payment_status']); ?></td>
                    <td><?php echo htmlspecialchars($customer['payment_date'] ? date('F', strtotime($customer['payment_date'])) : 'N/A'); ?></td>
                    <td><?php echo htmlspecialchars($customer['payment_date']); ?></td>
                    <td><?php echo htmlspecialchars($customer['due_date']); ?></td> <!-- Display due_date -->
                </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr>
                    <td colspan="8">No paid customers found.</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>
</body>
</html>
