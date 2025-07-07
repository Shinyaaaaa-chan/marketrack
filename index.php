<?php
ini_set('session.gc_maxlifetime', 86400); // 24 hours in seconds
session_set_cookie_params(86400);         // 24 hours in seconds

session_start();

include 'sidebar.php';
include 'db_connection.php';  // Database connection included

// Ensure that user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");  // Redirect if the user is not logged in
    exit();
}

// Fetch the user's information from the database
$user_id = $_SESSION['user_id'];
$query = "SELECT fullname, role FROM users WHERE id = '$user_id'";
$result = mysqli_query($conn, $query);

if (!$result) {
    die("Database query failed: " . mysqli_error($conn));
}

$user = mysqli_fetch_assoc($result);  // Fetch user data


// Calendar logic
$month = isset($_GET['month']) ? (int)$_GET['month'] : date('n');
$year = isset($_GET['year']) ? (int)$_GET['year'] : date('Y');

if ($month < 1) {
    $month = 12;
    $year--;
} elseif ($month > 12) {
    $month = 1;
    $year++;
}

$monthName = date('F', mktime(0, 0, 0, $month, 1, $year));
$firstDay = mktime(0, 0, 0, $month, 1, $year);
$daysInMonth = date('t', $firstDay);
$startDayOfWeek = date('w', $firstDay);

$calendar = [];
for ($i = 0; $i < $startDayOfWeek; $i++) {
    $calendar[] = "";
}
for ($i = 1; $i <= $daysInMonth; $i++) {
    $calendar[] = $i;
}


// Total Sales This Month
$salesQuery = "SELECT SUM(total_price) AS total_sales FROM orders WHERE MONTH(order_date) = MONTH(CURDATE()) AND YEAR(order_date) = YEAR(CURDATE())";
$salesResult = mysqli_query($conn, $salesQuery);
$totalSales = mysqli_fetch_assoc($salesResult)['total_sales'] ?? 0;

// Most In-Demand Product
$demandQuery = "SELECT p.name, SUM(oi.quantity) AS total_sold 
                FROM order_items oi 
                JOIN products p ON oi.product_id = p.id 
                GROUP BY oi.product_id 
                ORDER BY total_sold DESC 
                LIMIT 1";
$demandResult = mysqli_query($conn, $demandQuery);
$demandRow = mysqli_fetch_assoc($demandResult);
$topProduct = $demandRow['name'] ?? 'No data';
$topSold = $demandRow['total_sold'] ?? 0;

// Low Stock Products Count
$lowStockQuery = "SELECT COUNT(*) AS low_stock_count FROM product_variations WHERE stock <= 10"; 
$lowStockResult = mysqli_query($conn, $lowStockQuery);
$lowStockCount = mysqli_fetch_assoc($lowStockResult)['low_stock_count'] ?? 0;

// Total Orders
$ordersQuery = "SELECT COUNT(*) AS total_orders FROM orders";
$ordersResult = mysqli_query($conn, $ordersQuery);
$totalOrders = mysqli_fetch_assoc($ordersResult)['total_orders'] ?? 0;


?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - MarkeTrack</title>
    <link href="css/style.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.5.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="vendor/chart.js/Chart.min.js"></script>
</head>
<body id="page-top">

    <div id="content-wrapper" class="d-flex flex-column">
        <div id="content">

         <!-- Topbar -->
    <nav class="navbar navbar-expand navbar-light bg-light topbar mb-4 static-top shadow">
        <!-- Search Bar -->
        <form class="form-inline mr-auto ml-md-3 my-2 my-md-0 mw-100 navbar-search" method="GET" action="search_results.php">
            <div class="input-group">
                <input type="text" name="search_query" class="form-control bg-light border-0 small" placeholder="Search for..."
                    aria-label="Search" aria-describedby="basic-addon2">
                <div class="input-group-append">
                    <button class="btn btn-primary" type="submit">
                        <i class="fas fa-search fa-sm"></i>
                    </button>
                </div>
            </div>
        </form>

                <!-- Topbar Navbar -->
                <ul class="navbar-nav ml-auto">
<!-- Calendar -->
<li class="nav-item dropdown no-arrow mx-1">
    <a class="nav-link dropdown-toggle" href="#" id="calendarDropdown" role="button"
       data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
        <i class="fas fa-calendar-alt fa-fw"></i>
    </a>
    <div class="dropdown-menu dropdown-menu-right shadow animated--grow-in px-3 pt-2 pb-3"
         aria-labelledby="calendarDropdown" style="min-width: 300px;">

        <!-- Calendar Header with Buttons -->
        <div class="d-flex justify-content-between align-items-center bg-primary text-white px-3 py-2 rounded-top">
            <a href="?month=<?php echo $month - 1; ?>&year=<?php echo $year; ?>" style="color:white; font-weight:bold;">&#10094;</a>
            <div class="text-uppercase font-weight-bold"><?php echo $monthName . " " . $year; ?></div>
            <a href="?month=<?php echo $month + 1; ?>&year=<?php echo $year; ?>" style="color:white; font-weight:bold;">&#10095;</a>
        </div>

        <!-- Calendar Grid -->
        <div class="pt-3">
            <div class="text-center font-weight-bold" style="display: grid; grid-template-columns: repeat(7, 1fr);">
                <div>Sun</div><div>Mon</div><div>Tue</div><div>Wed</div><div>Thu</div><div>Fri</div><div>Sat</div>
            </div>


            
            <div style="display: grid; grid-template-columns: repeat(7, 1fr); text-align: center;">
                <?php
                foreach ($calendar as $day) {
                    echo "<div class='py-1'>" . ($day ?: "&nbsp;") . "</div>";
                }
                ?>
            </div>
        </div>
    </div>
</li>
                    <!-- Notifications -->
                    <li class="nav-item dropdown no-arrow mx-1">
                        <a class="nav-link dropdown-toggle" href="#" id="alertsDropdown" role="button"
                            data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                            <i class="fas fa-bell fa-fw"></i>
                            <span class="badge badge-danger badge-counter">3+</span>
                        </a>
                        <div class="dropdown-list dropdown-menu dropdown-menu-right shadow animated--grow-in"
                            aria-labelledby="alertsDropdown">
                            <h6 class="dropdown-header">Alerts Center</h6>
                            <a class="dropdown-item d-flex align-items-center" href="#">
                                <div class="mr-3">
                                    <div class="icon-circle bg-primary">
                                        <i class="fas fa-file-alt text-white"></i>
                                    </div>
                                </div>
                                <div>New report available!</div>
                            </a>
                        </div>
                    </li>
<!-- Right-aligned Profile and Logout Dropdown -->
<ul class="navbar-nav ml-auto">
    <!-- Profile and Logout Dropdown -->
    <li class="nav-item dropdown no-arrow">
        <a class="nav-link dropdown-toggle" href="#" id="userDropdown" role="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
        <span class="mr-2 d-none d-lg-inline text-gray-600 small">
    <?php
    
    if (isset($user['fullname'])) {
        echo $user['fullname'];
    } else {
        echo "User not found";  // You can display a default message if the user is not found
    }
    ?>
</span>

            <!-- Fixed Image -->
            <img class="img-profile rounded-circle" src="path_to_profile_picture.jpg" alt="User Profile" style="width: 30px; height: 30px; object-fit: cover;">
        </a>
        <div class="dropdown-menu dropdown-menu-right shadow animated--grow-in" aria-labelledby="userDropdown">
            <!-- Logout Button -->
            <button class="dropdown-item" onclick="logout()">
                <i class="fas fa-sign-out-alt fa-sm fa-fw mr-2 text-gray-400"></i>
                Logout
            </button>
        </div>
    </li>
</ul>
    </nav>


        <!-- Main Content -->
        <div class="main-content">

            <!-- Welcome Section -->
            <div class="card p-4 mb-4">
                <h2>Hello, Welcome Back</h2>
                <p>Today's Date: <?php echo date('l, F j, Y'); ?></p>
            </div>

            <div class="row">

<!-- Total Sales This Month -->
<div class="col-xl-3 col-md-6 mb-4">
    <div class="card border-left-success shadow h-100 py-2">
        <div class="card-body">
            <div class="text-xs font-weight-bold text-success text-uppercase mb-1">Total Sales (This Month)</div>
            <div class="h5 mb-0 font-weight-bold text-gray-800">₱<?php echo number_format($totalSales, 2); ?></div>
        </div>
    </div>
</div>

<!-- Most In-Demand Product -->
<div class="col-xl-3 col-md-6 mb-4">
    <div class="card border-left-info shadow h-100 py-2">
        <div class="card-body">
            <div class="text-xs font-weight-bold text-info text-uppercase mb-1">Most In-Demand Product</div>
            <div class="h6 mb-0 font-weight-bold text-gray-800"><?php echo $topProduct . " (" . $topSold . ")"; ?></div>
        </div>
    </div>
</div>

<!-- Low Stock Products -->
<div class="col-xl-3 col-md-6 mb-4">
    <div class="card border-left-warning shadow h-100 py-2">
        <div class="card-body">
            <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">Low Stock Products</div>
            <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $lowStockCount; ?></div>
        </div>
    </div>
</div>

<!-- Total Orders -->
<div class="col-xl-3 col-md-6 mb-4">
    <div class="card border-left-primary shadow h-100 py-2">
        <div class="card-body">
            <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">Total Orders</div>
            <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $totalOrders; ?></div>
        </div>
    </div>
</div>

</div>
       

          <!-- Demand Forecast Chart -->
          <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Demand Forecast vs Actual Sales</h6>
                </div>
                <div class="card-body">
                    <canvas id="salesChart" width="100%" height="400"></canvas>
                </div>
                <div class="card-footer small text-muted">Updated yesterday at 11:59 PM</div>
            </div>
          

<script>
// Demand Forecast vs Actual Sales (Linear Regression)
new Chart(document.getElementById('salesChart'), {
    type: 'line',
    data: {
        labels: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun'],
        datasets: [{
            label: 'Actual Sales',
            data: [120, 150, 180, 100, null, null], // May at June wala pang actual data
            borderColor: 'rgba(75, 192, 192, 1)',
            backgroundColor: 'rgba(75, 192, 192, 0.2)',
            tension: 0.3,
            fill: true
        },
        {
            label: 'Forecasted Sales (Linear Regression)',
            data: [115, 145, 175, 105, 130, 150], // Prediction hanggang June
            borderColor: 'rgba(255, 99, 132, 1)',
            backgroundColor: 'rgba(255, 99, 132, 0.2)',
            borderDash: [5, 5],
            tension: 0,
            fill: false
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: {
                position: 'top',
            },
            title: {
                display: true,
                text: 'Demand Forecast vs Actual Sales'
            }
        },
        scales: {
            xAxes: [{
                time: {
                    unit: 'month'
                },
                gridLines: {
                    display: false
                },
                ticks: {
                    maxTicksLimit: 6
                }
            }],
            yAxes: [{
                ticks: {
                    min: 0,
                    max: 200, // Adjust this to your maximum sales value
                    maxTicksLimit: 5
                },
                gridLines: {
                    color: "rgba(0, 0, 0, .125)",
                }
            }],
        }
    }
});


$(document).ready(function() {
    $('#alertDropdown').on('click', function(e) {
        e.preventDefault();
        $('#alertCenter').toggle();
        $('#calendarCenter').hide();
    });

    $('#calendarDropdown').on('click', function(e) {
        e.preventDefault();
        $('#calendarCenter').toggle();
        $('#alertCenter').hide();
    });

    $(document).on('click', function(event) {
        if (!$(event.target).closest('#alertDropdown, #alertCenter').length) {
            $('#alertCenter').hide();
        }
        if (!$(event.target).closest('#calendarDropdown, #calendarCenter').length) {
            $('#calendarCenter').hide();
        }
    });
});

function logout() {
    // Destroy session or clear session variables
    window.location.href = "logout.php"; // or wherever your logout script is
}

</script>
</body>
</html>
