<?php
session_start();
include 'db_connection.php'; // Include your database connection
include 'get_user_name.php'; // Include the file to get the logged-in user's name

// Check if the user is logged in
if (!isset($_SESSION['username'])) {
    header("Location: login.php");
    exit();
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Add Customer</title>
     <link rel="stylesheet" href="add.css">
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
        <!-- Main Content Area -->
        <div class="main-content">
            <h2>Add Customer Details</h2>

            <!-- Success Message Alert -->
            <?php if (isset($_SESSION['success'])): ?>
                <script>
                    alert("<?php echo $_SESSION['success']; ?>");
                </script>
                <?php unset($_SESSION['success']); // Clear the success message after displaying it ?>
            <?php endif; ?>

            <form id="addCustomerForm" action="process_add_customer.php" method="POST">
                <label for="name">Name:</label>
                <input type="text" id="name" name="name" required>

                <label for="phone">Phone:</label>
                <input type="tel" id="phone" name="phone" pattern="\d{10}" title="Please enter a valid 10-digit phone number" required>

                <label for="acc_no">Account No:</label>
                <input type="text" id="acc_no" name="acc_no" pattern="\d{12}" title="Account Number must be exactly 12 digits" required>

                <label for="amount">Amount to be Paid:</label>
                <input type="number" id="amount" name="amount" required>

                <label for="plan_type">Plan Type:</label>
                <select id="plan_type" name="plan_type" required>
                    <option value="Monthly">Monthly</option>
                    <option value="Quarterly">Quarterly</option>
                    <option value="Half-Yearly">Half-Yearly</option>
                    <option value="Yearly">Yearly</option>
                </select>

                 <label for="due_date">Due Date:</label>
                <select id="due_date" name="due_date" required>
                    <option value="Before 15th">Before 15th</option>
                    <option value="After 15th">After 15th</option>
                </select> 

                <!-- New field: RD Start Date -->
                <label for="date_started">RD Start Date:</label>
                <input type="date" id="date_started" name="date_started" required>

                <label for="paid_till_date">Last Amount Paid:</label>
                <input type="date" id="paid_till_date" name="paid_till_date" required>


                <input type="submit" value="Add Customer">
            </form>
        </div>
    </div>

    <script>
        // Function to show confirmation before submitting the form
        document.getElementById('addCustomerForm').onsubmit = function(e) {
            // Get the account number field value
            var accountNo = document.getElementById('acc_no').value;

            // Check if the account number is exactly 12 digits
            var isValidAccountNo = /^\d{12}$/.test(accountNo);
            
            if (isValidAccountNo) {
                // If valid account number, confirm with the user
                var confirmation = confirm("Are you sure you want to submit the account number: " + accountNo + "?");

                // If user cancels, prevent form submission
                if (!confirmation) {
                    e.preventDefault(); // Prevent form submission
                }
            } else {
                // If account number is invalid, show an alert and prevent form submission
                alert("Please enter a valid 12-digit account number.");
                e.preventDefault(); // Prevent form submission
            }
        }
    </script>
</body>
</html>
