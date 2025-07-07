<?php 
ini_set('session.gc_maxlifetime', 86400);
session_set_cookie_params(86400);
session_start();

include 'db_connection.php';
include 'sidebar.php';

// Check if user is logged in and their role
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role'])) {
    header("Location: login.php");
    exit;
}

$user_role = $_SESSION['role']; 

// Handle form submission for Trade and Marketing Team
if ($_SERVER['REQUEST_METHOD'] == 'POST' && $user_role === 'Trade and Marketing Team') {
    $promotion_type = $_POST['promotion_type'];
    $promo_title = $_POST['promo_title'];
    $promo_description = $_POST['promo_description'];
    $start_date = $_POST['start_date'];
    $end_date = $_POST['end_date'];
    $min_purchase = $_POST['min_purchase'];
    $product_variations = $_POST['product_variations'] ?? [];

    if ($promotion_type == 'percentage_discount') {
        $discount_percentage = $_POST['discount_percentage'];

        $insert = $conn->prepare("INSERT INTO promotions (
            promotion_type, promo_title, promo_description, discount_percentage, 
            start_date, end_date, min_purchase, created_at, status, created_by
        ) VALUES (?, ?, ?, ?, ?, ?, ?, NOW(), 'pending', ?)");

        $insert->bind_param("sssdssdi", 
            $promotion_type, 
            $promo_title, 
            $promo_description, 
            $discount_percentage, 
            $start_date, 
            $end_date, 
            $min_purchase, 
            $_SESSION['user_id']
        );

        if ($insert->execute()) {
            $promotion_id = $insert->insert_id;

            foreach ($product_variations as $product_id) {
                $stmt = $conn->prepare("INSERT INTO promotion_products (promotion_id, product_id) VALUES (?, ?)");
                $stmt->bind_param("ii", $promotion_id, $product_id);
                $stmt->execute();
            }
        }
    }

    // Placeholder for other types:
    // buy1take1, fixed_discount, bundle
}

// Handle approval by Brand Manager
if ($user_role == 'Brand Manager') {
    if (isset($_GET['approve'])) {
        $promotion_id = (int) $_GET['approve'];
        $update = $conn->prepare("UPDATE promotions SET status = 'approved', approved_at = NOW() WHERE id = ?");
        $update->bind_param("i", $promotion_id);
        $update->execute();
    }

    if (isset($_GET['reject'])) {
        $promotion_id = (int) $_GET['reject'];
        $update = $conn->prepare("UPDATE promotions SET status = 'rejected', approved_at = NOW() WHERE id = ?");
        $update->bind_param("i", $promotion_id);
        $update->execute();
    }
}

// Fetch promotions
if ($user_role == 'Brand Manager') {
    $promotions_result = $conn->query("SELECT * FROM promotions WHERE status = 'pending'");
} else {
    $promotions_result = $conn->query("SELECT * FROM promotions");
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Promotion Management</title>
    <link href="css/style.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
</head>
<body style="background-color: #f8f9fa;">
<div class="container py-4 bg-white rounded shadow" style="max-height: 90vh; overflow-y: auto;">
    <h2 class="mb-4 text-danger fw-bold">Pr
        omotion Management</h2>

    <?php if ($user_role === 'Trade and Marketing Team'): ?>
    <div class="card mb-4 border-0 shadow-sm">
        <div class="card-header bg-danger text-white fw-semibold">Create New Promotion</div>
        <div class="card-body">
            <form method="POST">
                <!-- Promotion Type -->
                <div class="mb-3">
                    <label class="form-label">Promotion Type</label>
                    <select name="promotion_type" id="promotion_type" class="form-select" required>
                        <option value="" disabled selected>Select Promotion Type</option>
                        <option value="percentage_discount">Percentage Discount</option>
                        <option value="buy1take1">Buy 1 Take 1</option>
                        <option value="fixed_discount">Fixed Amount Discount</option>
                        <option value="bundle">Bundle (e.g. 3 for ₱100)</option>
                        <option value="other">Other</option>
                    </select>
                </div>

                <!-- Custom Promotion Type (if "Other" selected) -->
                <div id="custom_promo_type_group" class="mb-3" style="display: none;">
                    <label class="form-label">Specify Promotion Type</label>
                    <input type="text" name="custom_promotion_type" class="form-control" placeholder="Enter custom promotion type">
                </div>

                <!-- Title -->
                <div class="mb-3">
                    <label class="form-label">Promotion Title</label>
                    <input type="text" name="promo_title" class="form-control" required>
                </div>

                <!-- Description -->
                <div class="mb-3">
                    <label class="form-label">Promotion Description</label>
                    <textarea name="promo_description" class="form-control" rows="3" required></textarea>
                </div>

                <!-- Percentage Discount Section -->
                <div id="percentage_discount_section" class="mb-3" style="display:none;">
                    <label class="form-label">Discount Percentage</label>
                    <input type="number" name="discount_percentage" class="form-control" min="1" max="100">
                </div>

                <!-- Applicable Products -->
                <div class="mb-3">
                    <label class="form-label">Applicable Products</label>
                    <div class="border rounded p-3 bg-light" style="max-height: 200px; overflow-y: auto;">
                        <?php
                        $result = $conn->query("SELECT pv.id, p.name AS product_name, pv.flavor, pv.pack_size 
                                                FROM product_variations pv 
                                                JOIN products p ON p.id = pv.product_id");
                        while ($row = $result->fetch_assoc()):
                            $label = htmlspecialchars($row['product_name'] . " - " . $row['flavor'] . " (" . $row['pack_size'] . ")");
                            $id = $row['id'];
                        ?>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="product_variations[]" value="<?= $id ?>" id="pv<?= $id ?>">
                            <label class="form-check-label" for="pv<?= $id ?>"><?= $label ?></label>
                        </div>
                        <?php endwhile; ?>
                    </div>
                </div>

                <!-- Date Range -->
                <div class="row mb-3">
                    <div class="col-md-6 mb-2">
                        <label class="form-label">Start Date</label>
                        <input type="date" class="form-control" name="start_date" required>
                    </div>
                    <div class="col-md-6 mb-2">
                        <label class="form-label">End Date</label>
                        <input type="date" class="form-control" name="end_date" required>
                    </div>
                </div>

                <!-- Min Purchase -->
                <div class="mb-3">
                    <label class="form-label">Minimum Purchase Amount</label>
                    <input type="number" class="form-control" name="min_purchase" required>
                </div>

                <!-- Submit -->
                <div class="text-end">
                    <button type="submit" class="btn btn-danger">Submit Promotion</button>
                </div>
            </form>
        </div>
    </div>
    <?php endif; ?>

    <!-- Promotion List -->
    <div class="card shadow-sm border-0">
        <div class="card-header bg-secondary text-white fw-semibold">Promotions List</div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered table-hover align-middle">
                    <thead class="table-light">
                        <tr>
                            <th>Promo Title</th>
                            <th>Status</th>
                            <th style="width: 150px;">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($promotion = $promotions_result->fetch_assoc()): ?>
                        <tr>
                            <td><?= htmlspecialchars($promotion['promo_title']) ?></td>
                            <td>
                                <?php
                                    $status = htmlspecialchars($promotion['status']);
                                    $badge_class = match ($status) {
                                        'approved' => 'success',
                                        'pending' => 'warning',
                                        'rejected' => 'danger',
                                        default => 'secondary'
                                    };
                                ?>
                                <span class="badge bg-<?= $badge_class ?>"><?= ucfirst($status) ?></span>
                            </td>
                            <td>
                                <?php if ($user_role === 'Brand Manager'): ?>
                                    <a href="?approve=<?= $promotion['id'] ?>" class="btn btn-success btn-sm">Approve</a>
                                    <a href="?reject=<?= $promotion['id'] ?>" class="btn btn-danger btn-sm">Reject</a>
                                <?php else: ?>
                                    <span class="text-muted">View Only</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- JS for toggle logic -->
<script>
    const promoType = document.getElementById("promotion_type");
    const customTypeGroup = document.getElementById("custom_promo_type_group");
    const discountSection = document.getElementById("percentage_discount_section");

    promoType.addEventListener("change", function () {
        const selected = this.value;
        customTypeGroup.style.display = (selected === "other") ? "block" : "none";
        discountSection.style.display = (selected === "percentage_discount") ? "block" : "none";
    });
</script>
</body>

<!-- Optional JS for dropdown show/hide -->
<script>
    document.getElementById("promotion_type").addEventListener("change", function () {
        const section = document.getElementById("percentage_discount_section");
        section.style.display = (this.value === "percentage_discount") ? "block" : "none";
    });
</script>

<!-- JS Scripts -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
    document.getElementById("promotion_type").addEventListener("change", function () {
        const section = document.getElementById("percentage_discount_section");
        section.style.display = (this.value === "percentage_discount") ? "block" : "none";
    });
</script>
</body>
</html>
