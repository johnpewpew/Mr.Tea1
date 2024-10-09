<?php
include 'config.php';
session_start();

if (!isset($_SESSION['user_name'])) {
    header('location:index.php');
}

// Fetch all categories for category buttons
$categories = $conn->query("SELECT * FROM categories");

// Handle search and item addition
$search_term = $_GET['search'] ?? '';
$category_id = $_GET['category_id'] ?? null;

if (!empty($search_term)) {
    $item_query = $conn->prepare("SELECT * FROM items WHERE name LIKE ?");
    $like_term = "%" . $search_term . "%";
    $item_query->bind_param("s", $like_term);
    $item_query->execute();
    $items = $item_query->get_result();
} else {
    if ($category_id) {
        $item_query = $conn->prepare("SELECT * FROM items WHERE category_id = ?");
        $item_query->bind_param("i", $category_id);
        $item_query->execute();
        $items = $item_query->get_result();
    } else {
        $items = $conn->query("SELECT * FROM items");
    }
}

// Initialize an array for storing current order
if (!isset($_SESSION['order'])) {
    $_SESSION['order'] = [];
}

// Handle order actions
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['clear_order'])) {
        $_SESSION['order'] = [];
    } elseif (isset($_POST['pay_later'])) {
        // Prepare order details
        $orderDetails = '';
        foreach ($_SESSION['order'] as $order_item) {
            $orderDetails .= $order_item['name'] . ' x ' . $order_item['quantity'] . ' (' . $order_item['price'] . ' each)\n';
        }

        // Calculate total amount
        $totalAmount = 0;
        foreach ($_SESSION['order'] as $order_item) {
            $totalAmount += $order_item['price'] * $order_item['quantity'];
        }

        // Insert into pending transactions table with item details
        $transactionDate = date('Y-m-d H:i:s');
        $transaction_query = $conn->prepare("INSERT INTO pending_transactions (total_amount, order_details, transaction_date) VALUES (?, ?, ?)");
        $transaction_query->bind_param("dss", $totalAmount, $orderDetails, $transactionDate);
        $transaction_query->execute();

        $_SESSION['order'] = []; // Clear the order after saving it as pending
    } elseif (isset($_POST['pay_now'])) {
        // Existing "Pay Now" logic
        // See below for details...
    } elseif (isset($_POST['add_item'])) {
        $item_id = $_POST['item_id'];
        $quantity = $_POST['quantity'];
        $size = $_POST['size']; // Get the selected size

        // Fetch the item details from the database
        $item_query = $conn->prepare("SELECT * FROM items WHERE id = ?");
        $item_query->bind_param("i", $item_id);
        $item_query->execute();
        $item = $item_query->get_result()->fetch_assoc();

        // Determine the price based on the size
        $price = ($size == 'Large') ? $item['large_price'] : $item['medium_price'];

        // Check if the requested quantity is available
        if ($item['quantity'] >= $quantity) {
            $existing_item_index = null;
            foreach ($_SESSION['order'] as $index => $order_item) {
                if ($order_item['id'] === $item['id'] && $order_item['size'] === $size) {
                    $existing_item_index = $index;
                    break;
                }
            }

            if ($existing_item_index === null) {
                $_SESSION['order'][] = [
                    'id' => $item['id'],
                    'name' => $item['name'] . " (" . $size . ")", // Include size in the name
                    'quantity' => $quantity,
                    'price' => $price, // Set the price based on size
                    'size' => $size, // Store size in the order
                    'image' => $item['image'] // Store the image URL
                ];
            } else {
                $available_quantity = $item['quantity'] - $_SESSION['order'][$existing_item_index]['quantity'];
                if ($available_quantity >= $quantity) {
                    $_SESSION['order'][$existing_item_index]['quantity'] += $quantity;
                } else {
                    echo "<script>alert('Insufficient stock for {$item['name']}. Available: $available_quantity');</script>";
                }
            }
        } else {
            echo "<script>alert('Insufficient stock for {$item['name']}. Available: {$item['quantity']}');</script>";
        }
    }
}

// Calculate total amount for the order
$totalAmount = 0;
foreach ($_SESSION['order'] as $order_item) {
    $totalAmount += $order_item['price'] * $order_item['quantity'];
}

// Payment logic
$payment = $change = $error = "";

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["pay_now"])) {
    if (!empty($_POST["payment-input"])) {
        $payment = floatval($_POST["payment-input"]);
        if ($payment >= $totalAmount) {
            $change = $payment - $totalAmount;

            // Update daily sales
            $today = date('Y-m-d');
            $check_sales_query = $conn->prepare("SELECT total_sales FROM daily_sales WHERE date = ?");
            $check_sales_query->bind_param("s", $today);
            $check_sales_query->execute();
            $result = $check_sales_query->get_result();

            if ($result->num_rows > 0) {
                $current_sales = $result->fetch_assoc()['total_sales'];
                $new_sales = $current_sales + $totalAmount;
                $update_sales_query = $conn->prepare("UPDATE daily_sales SET total_sales = ? WHERE date = ?");
                $update_sales_query->bind_param("ds", $new_sales, $today);
                $update_sales_query->execute();
            } else {
                $insert_sales_query = $conn->prepare("INSERT INTO daily_sales (date, total_sales) VALUES (?, ?)");
                $insert_sales_query->bind_param("sd", $today, $totalAmount);
                $insert_sales_query->execute();
            }

            // Deduct item quantities from inventory
            foreach ($_SESSION['order'] as $order_item) {
                $item_id = $order_item['id'];
                $item_query = $conn->prepare("SELECT quantity FROM items WHERE id = ?");
                $item_query->bind_param("i", $item_id);
                $item_query->execute();
                $item_result = $item_query->get_result()->fetch_assoc();

                $new_quantity = $item_result['quantity'] - $order_item['quantity'];
                $update_query = $conn->prepare("UPDATE items SET quantity = ? WHERE id = ?");
                $update_query->bind_param("ii", $new_quantity, $item_id);
                $update_query->execute();
            }

            // Prepare order details for transaction record
            $orderDetails = '';
            foreach ($_SESSION['order'] as $order_item) {
                $orderDetails .= $order_item['name'] . ' x ' . $order_item['quantity'] . ' (' . $order_item['price'] . ' each)\n';
            }

            // Insert transaction into transactions table
            $paymentStatus = "Paid";
            $transaction_query = $conn->prepare("INSERT INTO transactions (total_amount, order_details, payment_status) VALUES (?, ?, ?)");
            $transaction_query->bind_param("dss", $totalAmount, $orderDetails, $paymentStatus);
            $transaction_query->execute();

            $_SESSION['order'] = []; // Clear the order after successful payment
            $totalAmount = 0; // Reset total amount
        } else {
            $error = "Insufficient payment. Please enter a valid amount.";
        }
    } else {
        $error = "Please enter a valid payment amount.";
    }
}
?>



<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order Management</title>
    <link rel="stylesheet" href="meme.css">
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@100..900&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons+Outlined" rel="stylesheet">
</head>
<body>
<div class="container">
        <!-- Sidebar Navigation -->
        <div class="sidebar">
        <?php include 'order_side.php'?>
        </div>

        <!-- Main Content -->
        <div class="main-content">
            <div class="search-container">
                <form method="GET" action="order_management.php">
                    <input type="text" placeholder="Search" name="search" id="search-input">
                    <button class="search-button" type="submit"><h3>Search</h3></button>
                </form>
            </div>
            <div class="buttons-container">
                <?php while ($row = $categories->fetch_assoc()) { ?>
                    <form method="GET" action="order_management.php" style="display: inline;">
                        <input type="hidden" name="category_id" value="<?= $row['id'] ?>">
                        <button class="category-button" type="submit"><h5><?= $row['name'] ?></h5></button>
                    </form>
                <?php } ?>
                <form method="GET" action="order_management.php" style="display: inline;">
                    <button class="category-button" type="submit"><h5>All Items</h5></button>
                </form>
            </div>

            <div class="product-container">
                <div class="product-grid">
                    <?php while ($item = $items->fetch_assoc()) { ?>
                        <?php if ($item['quantity'] > 0): ?>
                            <div class="product-item" data-item-id="<?= $item['id'] ?>">
                                <img src="<?= $item['image'] ?>" alt="Product Image">
                                <h5><?= $item['name'] ?></h5>
                                <p>Medium Price: ₱<?= $item['medium_price'] ?></p>
                                <p>Large Price: ₱<?= $item['large_price'] ?></p>
                            </div>
                        <?php else: ?>
                            <div class="product-item out-of-stock" data-item-id="<?= $item['id'] ?>">
                                <img src="<?= $item['image'] ?>" alt="Product Image">
                                <h5><?= $item['name'] ?> (Out of Stock)</h5>
                                <p>Medium Price: ₱<?= $item['medium_price'] ?></p>
                                <p>Large Price: ₱<?= $item['large_price'] ?></p>
                            </div>
                        <?php endif; ?>
                    <?php } ?>
                </div>
                <div class="pagination">
                    <!-- Add pagination logic here -->
                </div>
            </div>

            <!-- Order Section -->
            <div class="order-section">
                <div class="order-header">
                    <h1>Current Order</h1>
                    <form method="POST" action="order_management.php">
                        <button class="clear-button" type="submit" name="clear_order"><h3>Clear</h3></button>
                    </form>
                </div>
                
                <div class="order-list" id="order-list">
                <?php if (!empty($_SESSION['order'])): ?>
                    <ul>
                        <?php foreach ($_SESSION['order'] as $order_item): ?>
                            <li class="order-item">
                                <img src="<?= $order_item['image'] ?>" alt="Item Image" class="order-item-image">
                                <div class="order-item-details">
                                    <h5><?= $order_item['name'] ?></h5>
                                    <p>₱<?= $order_item['price'] ?></p>
                                </div>
                                <div class="order-item-actions">
                                    <input type="number" value="<?= $order_item['quantity'] ?>" class="item-quantity" min="1">
                                    <span class="material-icons-outlined delete-icon">delete</span>
                                </div>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php else: ?>
                    <p>No items in the order</p>
                <?php endif; ?>
                </div>

                <div class="order-footer">
                    <p>Total: ₱<span id="total-amount"><?= number_format($totalAmount, 2); ?></span></p>
                    <div class="payment-buttons">
                        <form method="POST" action="order_management.php">
                            <button type="submit" class="pay-later" name="pay_later"><h1>Pay Later</h1></button>
                        </form>
                        <button class="pay-now" id="pay-now-btn"><h1>Pay Now</h1></button>
                    </div>
                </div>
            </div>
        </div>

    <!-- Quantity Input Popup -->
    <!-- Quantity Input Popup -->
<div class="popup" id="quantity-popup">
    <form method="POST" action="order_management.php">
        <input type="hidden" id="item-id-input" name="item_id">
        <label for="size">Select Size:</label>
        <select id="size" name="size" required>
            <option value="Medium">Medium</option>
            <option value="Large">Large</option>
        </select>
        <label for="quantity">Enter Quantity:</label>
        <input type="number" id="quantity" name="quantity" min="1" required>
        <button type="submit" name="add_item">Add to Order</button>
    </form>
</div>

    <div class="overlay" id="overlay"></div>

    <script>
        // Open popup to enter quantity when product is clicked
        const productItems = document.querySelectorAll('.product-item');
        const popup = document.getElementById('quantity-popup');
        const overlay = document.getElementById('overlay');
        const itemIdInput = document.getElementById('item-id-input');

        productItems.forEach(item => {
          item.addEventListener('click', () => {
          const itemId = item.getAttribute('data-item-id');
          itemIdInput.value = itemId;
          popup.style.display = 'block';
          overlay.style.display = 'block';
        });
    });

// Close popup when clicking outside
overlay.addEventListener('click', () => {
    popup.style.display = 'none';
    overlay.style.display = 'none';
});</script>


 <!-- Payment Input Popup -->
 <!-- Payment Input Popup -->
<div class="popup" id="payment-popup" style="display: none;">
    <h2>Payment</h2>
    <p>Total: ₱<span id="popup-total"><?= number_format($totalAmount, 2); ?></span></p>
    <p>Change: ₱ - <span id="popup-change">0.00</span></p> <!-- Changed ID for change display -->

    <form method="POST" action="">
        <div class="input-display">
            <input type="text" id="payment-input" name="payment-input" placeholder="0" 
                   value="<?= htmlspecialchars($payment); ?>" oninput="calculateChange(<?= $totalAmount; ?>)">
            <button type="button" onclick="clearInput()">⨉</button>
        </div>

        <!-- Numpad for Payment Input -->
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

        <!-- Submit Payment Button -->
        <button type="submit" class="pay-button" name="pay_now">Pay</button>
    </form>

    <!-- Cancel Button -->
    <button class="cancel-button" onclick="cancelOrder()">Cancel</button>

    <?php if (!empty($error)): ?>
        <p style="color: red;"><?= $error; ?></p>
    <?php endif; ?>
</div>

<!-- Overlay -->
<div class="overlay" id="popup-overlay" style="display: none;"></div>

<script>
    // Show Payment Popup
    document.getElementById('pay-now-btn').addEventListener('click', function() {
        document.getElementById('payment-popup').style.display = 'block';
        document.getElementById('popup-overlay').style.display = 'block';
    });

    // Close Popup when clicking outside
    document.getElementById('popup-overlay').addEventListener('click', () => {
        cancelOrder();
    });

    // Add Number to Payment Input
    function addNumber(number) {
        let input = document.getElementById('payment-input');
        input.value += number;
        calculateChange(<?= $totalAmount; ?>); // Recalculate change
    }

    // Clear Input
    function clearInput() {
        document.getElementById('payment-input').value = '';
        document.getElementById('popup-change').innerText = '0.00'; // Reset change display
    }

    // Calculate Change
    function calculateChange(totalAmount) {
        const paymentInput = parseFloat(document.getElementById('payment-input').value) || 0;
        const change = paymentInput - totalAmount;

        // Set change to 0 if negative
        const displayChange = change > 0 ? change.toFixed(2) : "0.00";

        // Update the change display
        document.getElementById('popup-change').innerText = displayChange;
    }

    // Cancel Payment Process
    function cancelOrder() {
        document.getElementById('payment-input').value = '';
        document.getElementById('popup-change').innerText = '0.00'; // Reset change display
        document.getElementById('payment-popup').style.display = 'none';
        document.getElementById('popup-overlay').style.display = 'none';
    }
</script>

</body>
</html>