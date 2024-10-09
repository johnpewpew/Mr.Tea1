<?php
include 'config.php';
session_start();

if (!isset($_SESSION['user_name'])) {
    header('location:index.php');
}

// Fetch all pending transactions
$transactions_query = $conn->query("SELECT * FROM pending_transactions ORDER BY transaction_date DESC");
$transactions = $transactions_query->fetch_all(MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="pending.css">
    <link rel="stylesheet" href="meme.css">
    <link rel="stylesheet" type="text/css" href="./css/main.css">
    <link rel="stylesheet" type="text/css" href="./css/admin.css">
    <link rel="stylesheet" type="text/css" href="./css/util.css">
    <title>Pending Transactions</title>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@100..900&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons+Outlined" rel="stylesheet">
</head>
<body>
<div class="order-side">
    <?php include 'order_side.php'; ?>
</div>
    
<div class="container">
    <h1>Pending Transactions</h1>

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
                    <th>Action</th>
                </tr>
            </thead>
            <tbody id="transaction-table-body">
                <?php foreach ($transactions as $transaction): ?>
                    <tr id="transaction-row-<?= htmlspecialchars($transaction['id']) ?>">
                        <td><?= htmlspecialchars($transaction['id']) ?></td>
                        <td><?= date("Y-m-d H:i:s", strtotime($transaction['transaction_date'])) ?></td>
                        <td class="order-details"><?= htmlspecialchars($transaction['order_details']) ?></td>
                        <td><?= number_format($transaction['total_amount'], 2) ?></td>
                        <td>
                            <button class="pay-now-button" onclick="payNow(<?= htmlspecialchars($transaction['id']) ?>, <?= htmlspecialchars($transaction['total_amount']) ?>)">Pay Now</button>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Payment Popup -->
<div class="popup" id="payment-popup">
    <h2>Payment</h2>
    <p>Total: ₱<span id="popup-total">0.00</span></p>
    <p>Change: ₱<span id="popup-change">0.00</span></p>

    <form id="payment-form">
        <div class="input-display">
            <input type="text" id="payment-input" name="payment-input" placeholder="0" oninput="calculateChange()">
            <button type="button" onclick="clearInput()">⨉</button>
        </div>

        <div class="num-pad-container">
            <div class="num-pad">
                <button type="button" onclick="addNumber(7)">7</button>
                <button type="button" onclick="addNumber(8)">8</button>
                <button type="button" onclick="addNumber(9)">9</button>
                <button type="button" onclick="addNumber(4)">4</button>
                <button type="button" onclick="addNumber(5)">5</button>
                <button type="button" onclick="addNumber(6)">6</button>
                <button type="button" onclick="addNumber(1)">1</button>
                <button type="button" onclick="addNumber(2)">2</button>
                <button type="button" onclick="addNumber(3)">3</button>
                <button type="button" onclick="addNumber('.')">.</button>
                <button type="button" onclick="addNumber(0)">0</button>
                <button type="button" onclick="addNumber('00')">00</button>
            </div>
        </div>

        <button type="button" class="pay-button" onclick="submitPayment()">Pay</button>
        <button type="button" class="cancel-button" onclick="cancelOrder()">Cancel</button>
    </form>
</div>

<!-- Overlay -->
<div class="overlay" id="popup-overlay"></div>

<script>
    let currentTransactionId; // Global variable to store current transaction ID
    const transactions = <?= json_encode($transactions) ?>; // Pass PHP transactions to JavaScript

    function searchTransactions() {
        const searchTerm = document.getElementById('search-transaction').value.toLowerCase();
        const rows = document.querySelectorAll('#transaction-table-body tr');

        rows.forEach(row => {
            const orderDetails = row.cells[2].textContent.toLowerCase();
            row.style.display = orderDetails.includes(searchTerm) ? '' : 'none';
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
            <tr id="transaction-row-${transaction.id}">
                <td>${transaction.id}</td>
                <td>${new Date(transaction.transaction_date).toLocaleString()}</td>
                <td><pre>${transaction.order_details}</pre></td>
                <td>${parseFloat(transaction.total_amount).toFixed(2)}</td>
                <td><button class="pay-now-button" onclick="payNow(${transaction.id}, ${transaction.total_amount})">Pay Now</button></td>
            </tr>
            `;
            tbody.innerHTML += row;
        });
    }

    function payNow(transactionId, totalAmount) {
        currentTransactionId = transactionId; // Store the current transaction ID
        document.getElementById('popup-total').innerText = totalAmount.toFixed(2);
        document.getElementById('popup-change').innerText = '0.00'; // Reset change display
        document.getElementById('payment-popup').style.display = 'block';
        document.getElementById('popup-overlay').style.display = 'block';
    }

    function addNumber(number) {
        let input = document.getElementById('payment-input');
        input.value += number;
        calculateChange();
    }

    function clearInput() {
        document.getElementById('payment-input').value = '';
        calculateChange();
    }

    function calculateChange() {
        const totalAmount = parseFloat(document.getElementById('popup-total').innerText) || 0;
        const paymentInput = parseFloat(document.getElementById('payment-input').value) || 0;
        const change = paymentInput - totalAmount;

        // Set change to 0 if negative
        const displayChange = change >= 0 ? change.toFixed(2) : "0.00";

        // Update the change display
        document.getElementById('popup-change').innerText = displayChange;
    }

    function submitPayment() {
    const paymentInput = parseFloat(document.getElementById('payment-input').value) || 0;
    const totalAmount = parseFloat(document.getElementById('popup-total').innerText) || 0;

    // Check if payment is sufficient
    if (paymentInput < totalAmount) {
        alert("Insufficient payment! Please enter a valid amount.");
        return;
    }

    // Make an AJAX call to process payment
    const xhr = new XMLHttpRequest();
    xhr.open('POST', 'process_payment.php', true);
    xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
    xhr.onload = function() {
        if (xhr.status === 200) {
            const response = JSON.parse(xhr.responseText);
            if (response.status === 'success') {
                alert("Payment successful!");

                // Remove the transaction row from the table
                const transactionRow = document.getElementById(`transaction-row-${currentTransactionId}`);
                if (transactionRow) {
                    transactionRow.remove(); // Remove the row from the DOM
                }

                // Optionally, close the popup
                cancelOrder();
            } else {
                alert(response.message);
            }
        } else {
            alert("Payment failed. Please try again.");
        }
    };

    xhr.send(`transaction_id=${currentTransactionId}&amount=${totalAmount}&payment=${paymentInput}`);
}


    function cancelOrder() {
        document.getElementById('payment-popup').style.display = 'none';
        document.getElementById('popup-overlay').style.display = 'none';
    }

    // Close popup when clicking outside of it
    window.onclick = function(event) {
        const popup = document.getElementById('payment-popup');
        const overlay = document.getElementById('popup-overlay');
        if (event.target === overlay) {
            popup.style.display = 'none';
            overlay.style.display = 'none';
        }
    };
</script>
</body>
</html>

