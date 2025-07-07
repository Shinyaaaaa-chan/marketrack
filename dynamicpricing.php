<?php
session_start();
include 'db_connection.php';  // Database connection
include 'sidebar.php';  // Include sidebar

// Fetch product variations and their dynamic pricing factors
$query = "SELECT pv.id, pv.name, pv.price_unit, dp.stock_level, dp.cost_of_production, dp.seasonality, dp.demand_factor
          FROM product_variations pv
          LEFT JOIN dynamic_pricing_factors dp ON pv.id = dp.product_variation_id";  // Join with dynamic_pricing_factors
$result = mysqli_query($conn, $query);

if (!$result) {
    die("Query failed: " . mysqli_error($conn));
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dynamic Pricing</title>
    <link href="css/style.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
</head>
<body id="page-top">

<div id="content-wrapper" class="d-flex flex-column">
    <div id="content" class="container mt-5">
        <h1 class="text-success">Dynamic Pricing</h1>

        <div class="card shadow mb-4">
            <div class="card-body">
                <table class="table table-bordered" id="pricingTable">
                    <thead class="thead-dark">
                        <tr>
                            <th>Product</th>
                            <th>Base Price</th>
                            <th>Demand Factor</th>
                            <th>Stock Level</th>
                            <th>Cost of Production</th>
                            <th>Seasonality</th>
                            <th>Adjusted Price</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($row = mysqli_fetch_assoc($result)) { ?>
                            <tr>
                                <td><?php echo $row['name']; ?></td>
                                <td><input type="number" value="<?php echo $row['price_unit']; ?>" class="form-control base-price"></td>
                                <td>
                                    <input type="number" step="0.1" value="<?php echo $row['demand_factor']; ?>" class="form-control demand-factor">
                                </td>
                                <td>
                                    <input type="number" value="<?php echo $row['stock_level']; ?>" class="form-control stock-level" readonly>
                                </td>
                                <td>
                                    <input type="number" value="<?php echo $row['cost_of_production']; ?>" class="form-control cost-of-production">
                                </td>
                                <td>
                                    <select class="form-control seasonality" name="seasonality">
                                        <option value="1.0" <?php echo ($row['seasonality'] == 'Normal') ? 'selected' : ''; ?>>Normal</option>
                                        <option value="1.2" <?php echo ($row['seasonality'] == 'Peak') ? 'selected' : ''; ?>>Peak</option>
                                        <option value="0.8" <?php echo ($row['seasonality'] == 'Off-Peak') ? 'selected' : ''; ?>>Off-Peak</option>
                                    </select>
                                </td>
                                <td class="adjusted-price">$<?php echo number_format($row['price_unit'], 2); ?></td>
                            </tr>
                        <?php } ?>
                    </tbody>
                </table>
            </div>
        </div>

    </div>
</div>

<script src="vendor/jquery/jquery.min.js"></script>
<script src="vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
<script src="js/sb-admin-2.min.js"></script>

<script>
    // Function to update the price dynamically
    function updatePrices() {
        const rows = document.querySelectorAll('#pricingTable tbody tr');

        rows.forEach(row => {
            const basePriceInput = row.querySelector('.base-price');
            const demandFactorInput = row.querySelector('.demand-factor');
            const stockLevelInput = row.querySelector('.stock-level');
            const costOfProductionInput = row.querySelector('.cost-of-production');
            const seasonalityInput = row.querySelector('.seasonality');
            const adjustedPriceCell = row.querySelector('.adjusted-price');

            const basePrice = parseFloat(basePriceInput.value) || 0;
            const demandFactor = parseFloat(demandFactorInput.value) || 1;
            const stockLevel = parseInt(stockLevelInput.value) || 0;
            const costOfProduction = parseFloat(costOfProductionInput.value) || 0;
            const seasonality = parseFloat(seasonalityInput.value) || 1.0;

            // Adjust price based on demand factor
            let adjustedPrice = basePrice * demandFactor;

            // Seasonality adjustment (increase price in peak season)
            adjustedPrice *= seasonality;

            // If stock is low (less than 50), increase price by 10%
            if (stockLevel < 50) {
                adjustedPrice *= 1.10;
            } 
            // If stock is high (greater than or equal to 50), decrease price by 5%
            else if (stockLevel >= 50) {
                adjustedPrice *= 0.95;
            }

            // Adjust price based on cost of production
            adjustedPrice += costOfProduction * 0.1;  // Adjust price by 10% of production cost increase

            // Update the displayed price with $ symbol
            adjustedPriceCell.textContent = `$${adjustedPrice.toFixed(2)}`;
        });
    }

    // Event listeners for real-time updates
    document.addEventListener('input', updatePrices);

    // Initial calculation on page load
    window.onload = updatePrices;
</script>

</body>
</html>
