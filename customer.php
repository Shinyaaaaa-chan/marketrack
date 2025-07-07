<?php
ini_set('session.gc_maxlifetime', 86400); // 24 hours in seconds
session_set_cookie_params(86400);         // 24 hours in seconds

session_start();
    












include 'db_connection.php'; // Make sure this path is correct based on your file structure
include 'sidebar.php';
$result = $conn->query("SELECT * FROM customers");


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

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Customers - MarkeTrack</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link href="vendor/bootstrap/css/bootstrap.min.css" rel="stylesheet">

    <style>
        .add-button {
            display: inline-block;
            margin: 20px 0;
            padding: 10px 15px;
            background-color: #4CAF50;
            color: white;
            text-decoration: none;
            border-radius: 5px;
        }
        .add-button:hover {
            background-color: #45a049;
        }
    </style>
</head>
<body>

     <!-- Main Content -->
     <div class="container-fluid">
            <div class="card shadow mb-4 p-4">
                <h3 class="mb-4 text-primary font-weight-bold">Registered Customers</h3>
              
                <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'Assistant Brand Manager'): ?>

                <div class="mb-3">
                    <a href="add_customer.php" class="btn btn-primary shadow-sm">
                        <i class="fas fa-user-plus mr-2"></i> Add Customer
                    </a>
                </div>
                <?php endif; ?>

<div class="table-responsive">
    <table class="table table-bordered table-hover">
        <thead class="thead-light">
            <tr>
                <th>ID</th>
                <th>Full Name</th>
                <th>Store Name</th>
                <th>Address</th>
                <th>Contact Number</th>
            </tr>
        </thead>
        <tbody>
            <?php while ($row = $result->fetch_assoc()): ?>
            <tr>
                <td><?= $row['id'] ?></td>
                <td><?= htmlspecialchars($row['fullname']) ?></td>
                <td><?= htmlspecialchars($row['store_name']) ?></td>
                <td><?= htmlspecialchars($row['address']) ?></td>
                <td><?= htmlspecialchars($row['contact_number']) ?></td>
            </tr>
            <?php endwhile; ?>
        </tbody>
    </table>
</div>

    </div> <!-- End of Content -->
</div> <!-- End of Content Wrapper -->

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
<script src="vendor/chart.js/Chart.min.js"></script>

</body>
</html>
