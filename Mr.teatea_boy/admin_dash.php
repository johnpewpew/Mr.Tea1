<?php
include 'config.php';
session_start();

if (!isset($_SESSION['admin_name'])) {
    header('location:index.php');
}

// Daily sales update logic after successful payment
if (isset($_POST['payment_success'])) { 
    $totalAmount = $_POST['total_amount']; 
    $today = date('Y-m-d');
    
    // Daily sales logic
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

    // Weekly sales logic
    $monday = date('Y-m-d', strtotime('monday this week'));
    $check_weekly_sales_query = $conn->prepare("SELECT total_sales FROM weekly_sales WHERE week_start_date = ?");
    $check_weekly_sales_query->bind_param("s", $monday);
    $check_weekly_sales_query->execute();
    $weekly_result = $check_weekly_sales_query->get_result();

    if ($weekly_result->num_rows > 0) {
        $current_weekly_sales = $weekly_result->fetch_assoc()['total_sales'];
        $new_weekly_sales = $current_weekly_sales + $totalAmount;
        $update_weekly_sales_query = $conn->prepare("UPDATE weekly_sales SET total_sales = ? WHERE week_start_date = ?");
        $update_weekly_sales_query->bind_param("ds", $new_weekly_sales, $monday);
        $update_weekly_sales_query->execute();
    } else {
        $insert_weekly_sales_query = $conn->prepare("INSERT INTO weekly_sales (week_start_date, total_sales) VALUES (?, ?)");
        $insert_weekly_sales_query->bind_param("sd", $monday, $totalAmount);
        $insert_weekly_sales_query->execute();
    } 
}

// Retrieve daily sales for display
$today = date('Y-m-d');
$daily_sales_query = $conn->prepare("SELECT total_sales FROM daily_sales WHERE date = ?");
$daily_sales_query->bind_param("s", $today);
$daily_sales_query->execute();
$daily_sales_result = $daily_sales_query->get_result();
$daily_sales = $daily_sales_result->num_rows > 0 ? $daily_sales_result->fetch_assoc()['total_sales'] : 0;

// Retrieve weekly sales for display
$monday = date('Y-m-d', strtotime('monday this week'));
$weekly_sales_query = $conn->prepare("SELECT total_sales FROM weekly_sales WHERE week_start_date = ?");
$weekly_sales_query->bind_param("s", $monday);
$weekly_sales_query->execute();
$weekly_sales_result = $weekly_sales_query->get_result();
$weekly_sales = $weekly_sales_result->num_rows > 0 ? $weekly_sales_result->fetch_assoc()['total_sales'] : 0;

// Monthly sales logic
$monthly_sales = []; 
for ($i = 1; $i <= 12; $i++) {
    $first_day_of_month = date("Y-$i-01");
    $last_day_of_month = date("Y-$i-t"); 
    $check_monthly_sales_query = $conn->prepare("SELECT SUM(total_sales) AS total_sales FROM daily_sales WHERE date BETWEEN ? AND ?");
    $check_monthly_sales_query->bind_param("ss", $first_day_of_month, $last_day_of_month);
    $check_monthly_sales_query->execute();
    $monthly_sales_result = $check_monthly_sales_query->get_result();
    $monthly_sales[$i] = $monthly_sales_result->num_rows > 0 ? $monthly_sales_result->fetch_assoc()['total_sales'] : 0;
}

// Annual sales logic
$current_year = date('Y-01-01'); 
$check_annual_sales_query = $conn->prepare("SELECT SUM(total_sales) AS total_sales FROM daily_sales WHERE date >= ?");
$check_annual_sales_query->bind_param("s", $current_year);
$check_annual_sales_query->execute();
$annual_sales_result = $check_annual_sales_query->get_result();
$annual_sales = $annual_sales_result->num_rows > 0 ? $annual_sales_result->fetch_assoc()['total_sales'] : 0;

// Fetch top 5 most sold products
$top_products_query = $conn->prepare("
    SELECT items.name, SUM(product_sales.quantity_sold) AS total_sold
    FROM product_sales
    JOIN items ON items.id = product_sales.product_id
    GROUP BY items.id
    ORDER BY total_sold DESC
    LIMIT 5
");
$top_products_query->execute();
$top_products_result = $top_products_query->get_result();

$top_product_names = [];
$top_product_sales = [];
while ($product = $top_products_result->fetch_assoc()) {
    $top_product_names[] = $product['name'];
    $top_product_sales[] = $product['total_sold'];
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" type="text/css" href="./css/main.css">
    <link rel="stylesheet" type="text/css" href="./css/admin.css">
    <link rel="stylesheet" type="text/css" href="./css/util.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/apexcharts/3.35.3/apexcharts.min.js"></script>
</head>
<body>
    <div class="container">
        <?php include 'sidebar.php'?>

        <div class="main">
            <div class="topbar">
                <div class="toggle">
                    <ion-icon name="menu-outline"></ion-icon>
                </div>
            </div>
            
            <main class="main-container">
                <div class="cardBox">
                    <div class="card">
                        <div>
                            <div class="numbers"><?= number_format($annual_sales, 2); ?></div>
                            <div class="cardName">Annual Sales</div>
                        </div>
                        <div class="iconBx">
                            <ion-icon name="eye-outline"></ion-icon>
                        </div>
                    </div>
                    <div class="card">
                        <div>
                            <div class="numbers"><?= number_format($monthly_sales[date('n')], 2); ?></div>
                            <div class="cardName">Monthly Sales</div>
                        </div>
                        <div class="iconBx">
                            <ion-icon name="cart-outline"></ion-icon>
                        </div>
                    </div>
                    <div class="card">
                        <div>
                            <div class="numbers"><?= number_format($weekly_sales, 2); ?></div>
                            <div class="cardName">Weekly Sales</div>
                        </div>
                        <div class="iconBx">
                        <ion-icon name="server-outline"></ion-icon>
                        </div>
                    </div>
                    <div class="card">
                        <div>
                            <div class="numbers"><?= number_format($daily_sales, 2); ?></div>
                            <div class="cardName">Daily Sales</div>
                        </div>
                        <div class="iconBx">
                            <ion-icon name="cash-outline"></ion-icon>
                        </div>
                    </div>
                </div>

                <div class="charts">
                    <div class="charts-card">
                        <p class="chart-title">Sales Performance</p>
                        <div id="sales-chart"></div>
                    </div>
                    <div class="charts-card">
                        <p class="chart-title">Top 5 Products</p>
                        <div id="bar-chart"></div>
                    </div>
                </div>
            </main>
        </div>
    </div>
    
    <div class="color-picker-container">
        <input type="color" id="salesColor" value="#4CAF50">
        <input type="color" id="topProductsColor" value="#FF5733">
    </div>

    <script>
        document.addEventListener("DOMContentLoaded", function() {
            // Sales performance chart
            const salesData = <?= json_encode(array_values($monthly_sales)); ?>;
            const salesOptions = {
                chart: {
                    type: 'line',
                    height: 350
                },
                stroke: {
                    curve: 'smooth', // Makes the line smooth
                    width: 3 // Increases line width for better visibility
                },
                series: [{
                    name: 'Sales',
                    data: salesData
                }],
                xaxis: {
                    categories: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'],
                    labels: {
                        style: {
                            colors: '#888', // Color of x-axis labels
                            fontSize: '12px' // Font size of x-axis labels
                        }
                    }
                },
                colors: [document.getElementById('salesColor').value],
                dataLabels: {
                    enabled: true, // Enable data labels
                    style: {
                        colors: ['#fff'], // Color of data labels
                    }
                },
                tooltip: {
                    theme: 'dark', // Dark tooltip for better visibility
                    x: {
                        show: true
                    },
                    y: {
                        formatter: function (value) {
                            return '₱' + value.toFixed(2); // Format value with currency
                        }
                    }
                }
            };
            const salesChart = new ApexCharts(document.querySelector("#sales-chart"), salesOptions);
            salesChart.render();

            // Top 5 products bar chart
            const productNames = <?= json_encode($top_product_names); ?>;
            const productSales = <?= json_encode($top_product_sales); ?>;
            const barOptions = {
                chart: {
                    type: 'bar',
                    height: 350
                },
                plotOptions: {
                    bar: {
                        horizontal: false,
                        endingShape: 'rounded',
                        columnWidth: '55%'
                    }
                },
                dataLabels: {
                    enabled: true, // Enable data labels on bars
                    style: {
                        colors: ['#fff'], // Color of data labels
                    }
                },
                series: [{
                    name: 'Products Sold',
                    data: productSales
                }],
                xaxis: {
                    categories: productNames,
                    labels: {
                        style: {
                            colors: '#888', // Color of x-axis labels
                            fontSize: '12px' // Font size of x-axis labels
                        }
                    }
                },
                colors: [document.getElementById('topProductsColor').value],
                tooltip: {
                    theme: 'dark', // Dark tooltip for better visibility
                    x: {
                        show: true
                    },
                    y: {
                        formatter: function (value) {
                            return value + ' units'; // Format value for tooltip
                        }
                    }
                }
            };
            const barChart = new ApexCharts(document.querySelector("#bar-chart"), barOptions);
            barChart.render();

            // Event listeners for color pickers
            document.getElementById('salesColor').addEventListener('input', function() {
                salesOptions.colors = [this.value];
                salesChart.updateOptions(salesOptions);
            });

            document.getElementById('topProductsColor').addEventListener('input', function() {
                barOptions.colors = [this.value];
                barChart.updateOptions(barOptions);
            });
        });
    </script>
</body>
</html>
