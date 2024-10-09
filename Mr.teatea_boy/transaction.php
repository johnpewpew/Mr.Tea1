<?php
include 'config.php';
session_start();

if (!isset($_SESSION['admin_name'])) {
    header('location:index.php');
}

// Fetch all transactions
$transactions_query = $conn->query("SELECT * FROM transactions ORDER BY transaction_date DESC");
$transactions = $transactions_query->fetch_all(MYSQLI_ASSOC); // Fetch all transactions at once for easier manipulation in JS
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="transaction.css">
    <title>Transactions</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" type="text/css" href="./css/main.css">
    <link rel="stylesheet" type="text/css" href="./css/admin.css">
    <link rel="stylesheet" type="text/css" href="./css/util.css">
</head>
<body>
    <?php include 'sidebar.php' ?>
    <div class="container">
        <h1>Transactions Record</h1>

        <div class="search-section">
            <input type="text" class="search-bar" placeholder="Search" id="search-transaction" onkeyup="searchTransactions()">
            <button class="search-button">Search</button>
            <select class="filter-dropdown" id="filter-dropdown" onchange="filterTransactions()">
                <option value="daily">Daily</option>
                <option value="weekly">Weekly</option>
                <option value="monthly">Monthly</option>
                <option value="yearly">Yearly</option>
            </select>
        </div>

        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th>Trans #</th>
                        <th>Date</th>
                        <th>Order</th>
                        <th>Total</th>
                        <th>Payment Status</th>
                    </tr>
                </thead>
            </table>
            <div class="scrollable-tbody">
                <table>
                    <tbody id="transaction-table-body">
                        <?php foreach ($transactions as $transaction) { ?>
                        <tr>
                            <td><?= $transaction['id'] ?></td>
                            <td><?= date("Y-m-d H:i:s", strtotime($transaction['transaction_date'])) ?></td>
                            <td class="order-details"><?= htmlspecialchars($transaction['order_details']) ?></td>
                            <td><?= number_format($transaction['total_amount'], 2) ?></td>
                            <td><?= htmlspecialchars($transaction['payment_status']) ?></td>
                        </tr>
                        <?php } ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <script>
        const transactions = <?= json_encode($transactions) ?>; // Pass PHP transactions to JavaScript

        function searchTransactions() {
            const searchTerm = document.getElementById('search-transaction').value.toLowerCase();
            const rows = document.querySelectorAll('#transaction-table-body tr');

            rows.forEach(row => {
                const orderDetails = row.cells[2].textContent.toLowerCase();
                if (orderDetails.includes(searchTerm)) {
                    row.style.display = '';
                } else {
                    row.style.display = 'none';
                }
            });
        }

        function filterTransactions() {
            const filterValue = document.getElementById('filter-dropdown').value;
            const filteredTransactions = transactions.filter(transaction => {
                const transactionDate = new Date(transaction.transaction_date);
                const now = new Date();
                let startDate;

                switch (filterValue) {
                    case 'daily':
                        startDate = new Date(now.getFullYear(), now.getMonth(), now.getDate());
                        break;
                    case 'weekly':
                        startDate = new Date(now.getFullYear(), now.getMonth(), now.getDate() - now.getDay());
                        break;
                    case 'monthly':
                        startDate = new Date(now.getFullYear(), now.getMonth(), 1);
                        break;
                    case 'yearly':
                        startDate = new Date(now.getFullYear(), 0, 1);
                        break;
                    default:
                        startDate = new Date(now.getFullYear(), now.getMonth(), now.getDate());
                }

                return transactionDate >= startDate; // Filter transactions based on the selected period
            });

            // Update the table with filtered transactions
            const tbody = document.getElementById('transaction-table-body');
            tbody.innerHTML = ''; // Clear existing rows

            filteredTransactions.forEach(transaction => {
                const row = `
                <tr>
                    <td>${transaction.id}</td>
                    <td>${new Date(transaction.transaction_date).toLocaleString()}</td>
                    <td><pre>${transaction.order_details}</pre></td>
                    <td>${parseFloat(transaction.total_amount).toFixed(2)}</td>
                    <td>${transaction.payment_status}</td>
                </tr>
                `;
                tbody.innerHTML += row;
            });
        }
    </script>
</body>
</html>
