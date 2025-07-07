<?php
session_start();
include 'db_connection.php';
include 'sidebar.php';

// Restrict access to Brand Manager only
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'Brand Manager') {
    header("Location: unauthorized.php");
    exit;
}

$success = "";
$error = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $fullname = trim($_POST['fullname']);
    $username = trim($_POST['username']);
    $password = $_POST['password'];
    $role = $_POST['role'];

    // Check if username already exists
    $check = $conn->prepare("SELECT id FROM users WHERE username = ?");
    $check->bind_param("s", $username);
    $check->execute();
    $result = $check->get_result();

    if ($result->num_rows > 0) {
        $error = "Username already exists. Please choose another one.";
    } else {
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $conn->prepare("INSERT INTO users (fullname, username, password, role) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("ssss", $fullname, $username, $hashed_password, $role);

        if ($stmt->execute()) {
            $success = "Account successfully created!";
        } else {
            $error = "Error occurred while creating account.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Add New Account - MarkeTrack</title>
    <link href="css/sb-admin-2.min.css" rel="stylesheet">
    <link href="css/style.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
       .larger-form-label {
    font-size: 1.1rem;
}

.larger-form-control,
.larger-form-select {
    font-size: 1.1rem;
    padding: 0.75rem 1rem;
}

.btn-larger {
    padding: 0.75rem 1.5rem;
    font-size: 1.1rem;
}

    </style>
</head>
<body>


    
          <!-- Main Content -->
<div class="container-fluid">
    <div class="card shadow mb-4 p-4">
        <h3 class="mb-4 text-primary font-weight-bold">Add New Account</h3>

        <?php if ($success): ?>
            <div class="alert alert-success"><?= $success ?></div>
        <?php elseif ($error): ?>
            <div class="alert alert-danger"><?= $error ?></div>
        <?php endif; ?>

        <form action="" method="POST">
            <div class="form-group">
                <label class="larger-form-label">Full Name</label>
                <input type="text" name="fullname" class="form-control larger-form-control" required>
            </div>

            <div class="form-group">
                <label class="larger-form-label">Username</label>
                <input type="text" name="username" class="form-control larger-form-control" required>
            </div>

            <div class="form-group">
                <label class="larger-form-label">Password</label>
                <input type="password" name="password" class="form-control larger-form-control" required>
            </div>

            <div class="form-group">
                <label class="larger-form-label">Role</label>
                <select name="role" class="form-control larger-form-select" required>
                    <option value="Brand Manager">Brand Manager</option>
                    <option value="Assistant Brand Manager">Assistant Brand Manager</option>
                    <option value="Merchandising Marketing Team">Merchandising Marketing Team</option>
                    <option value="Trade and Marketing Team">Trade and Marketing Team</option>
                    <option value="Logistics">Logistics</option>
                </select>
            </div>

            <button type="submit" class="btn btn-primary btn-larger shadow-sm mt-3">
                <i class="fas fa-plus mr-2"></i> Create Account
            </button>
        </form>
    </div>
</div>


        <!-- Scroll to Top Button-->
        <a class="scroll-to-top rounded" href="#page-top">
            <i class="fas fa-angle-up"></i>
        </a>

    </div>
</div>

<!-- Scripts -->
<script src="vendor/jquery/jquery.min.js"></script>
<script src="vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
<script src="vendor/jquery-easing/jquery.easing.min.js"></script>
<script src="js/sb-admin-2.min.js"></script>
</body>
</html>
