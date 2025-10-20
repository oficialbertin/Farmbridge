<?php require 'db.php'; require 'session_helper.php'; if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'farmer') { header('Location: login.php'); exit; } $msg = ''; if ($_SERVER['REQUEST_METHOD'] === 'POST') { $name = $conn->real_escape_string($_POST['name']); $quantity = (int)$_POST['quantity']; $unit = $conn->real_escape_string($_POST['unit']); $price = (float)$_POST['price']; $description = $conn->real_escape_string($_POST['description']); $district = $conn->real_escape_string(trim($_POST['district'] ?? '')); $sector = $conn->real_escape_string(trim($_POST['sector'] ?? '')); $cell = $conn->real_escape_string(trim($_POST['cell'] ?? '')); $image = null; if (!empty($_FILES['image']['name'])) { $target_dir = 'uploads/'; if (!is_dir($target_dir)) mkdir($target_dir); $image = $target_dir . uniqid() . '_' . basename($_FILES['image']['name']); move_uploaded_file($_FILES['image']['tmp_name'], $image); } $farmer_id = $_SESSION['user_id'];
// Try to insert location columns if they exist; fallback to description append
$colsRes = $conn->query("SHOW COLUMNS FROM crops LIKE 'district'"); $hasDistrict = $colsRes && $colsRes->num_rows > 0; $colsRes = $conn->query("SHOW COLUMNS FROM crops LIKE 'sector'"); $hasSector = $colsRes && $colsRes->num_rows > 0; $colsRes = $conn->query("SHOW COLUMNS FROM crops LIKE 'cell'"); $hasCell = $colsRes && $colsRes->num_rows > 0;
if ($hasDistrict && $hasSector && $hasCell) {
	$sql = "INSERT INTO crops (farmer_id, name, description, quantity, unit, price, image, district, sector, cell) VALUES ($farmer_id, '$name', '$description', $quantity, '$unit', $price, " . ($image ? "'$image'" : "NULL") . ", '$district', '$sector', '$cell')";
} else {
	$descExtra = trim(($district||$sector||$cell) ? (" Location: " . $district . '/' . $sector . '/' . $cell) : '');
	$sql = "INSERT INTO crops (farmer_id, name, description, quantity, unit, price, image) VALUES ($farmer_id, '$name', '" . $description . $conn->real_escape_string($descExtra) . "', $quantity, '$unit', $price, " . ($image ? "'$image'" : "NULL") . ")";
}
if ($conn->query($sql)) { $msg = '<div class="alert alert-success">Crop listed successfully! Redirecting...</div><script>setTimeout(function(){ window.location.href = "farmer_crops.php"; }, 1200);</script>'; } else { $msg = '<div class="alert alert-danger">Error: ' . $conn->error . '</div>'; } } include 'header.php'; ?>
<main class="container mt-5">
    <h2>List a New Crop</h2>
    <?= $msg ?>
    <form id="crop-form" enctype="multipart/form-data" method="post">
        <div class="mb-3">
            <input type="text" name="name" class="form-control" placeholder="Crop Name" required onblur="getMarketPrice()">
        </div>
        <div class="mb-3">
            <input type="number" name="quantity" class="form-control" placeholder="Quantity" required>
        </div>
        <div class="mb-3">
            <input type="text" name="unit" class="form-control" placeholder="Unit (e.g. kg)" value="kg">
        </div>
        <div class="mb-3">
            <input type="number" name="price" class="form-control" placeholder="Price per unit" required>
        </div>
        <div class="mb-3">
            <textarea name="description" class="form-control" placeholder="Description"></textarea>
        </div>
        <div class="mb-3 row g-2">
            <div class="col-12 col-md-4"><input type="text" name="district" class="form-control" placeholder="District" required></div>
            <div class="col-6 col-md-4"><input type="text" name="sector" class="form-control" placeholder="Sector" required></div>
            <div class="col-6 col-md-4"><input type="text" name="cell" class="form-control" placeholder="Cell" required></div>
        </div>
        <div class="mb-3">
            <label for="crop-image" class="form-label">Crop Image (optional)</label>
            <input type="file" name="image" class="form-control" id="crop-image" accept="image/*">
        </div>
        <!-- AI Price Suggestion -->
        <div class="mb-3">
            <label for="suggested_price" class="form-label">AI Suggested Price</label>
            <input type="text" id="suggested_price" class="form-control" readonly>
            <button type="button" class="btn btn-info mt-2" onclick="getSuggestedPrice()">Get Suggested Price</button>
        </div>
        <!-- Market Price Display -->
        <div class="mb-3">
            <label for="market_price" class="form-label">Latest Market Price (External)</label>
            <input type="text" id="market_price" class="form-control" readonly>
        </div>
        <button type="submit" class="btn btn-success">Add Crop</button>
        <a href="farmer.php" class="btn btn-link">Back to Dashboard</a>
    </form>
</main>
<?php include 'footer.php'; ?>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<link rel="stylesheet" href="css/style.css">
<script>
function getSuggestedPrice() {
    var cropName = document.querySelector('[name="name"]').value || '';
    var quantity = document.querySelector('[name="quantity"]').value || 1;
    if (!cropName) { document.getElementById('suggested_price').value = 'Please enter crop name first'; return; }
    fetch('market_price_api.php?commodity=' + encodeURIComponent(cropName))
        .then(response => response.json())
        .then(marketData => {
            if (marketData && marketData.price) {
                var basePrice = marketData.price;
                var suggestedPrice = basePrice;
                if (quantity >= 100) suggestedPrice *= 0.95; else if (quantity >= 50) suggestedPrice *= 0.97; else if (quantity >= 20) suggestedPrice *= 0.98;
                var currentMonth = new Date().getMonth() + 1;
                if ([3,4,5,6,9,10,11,12].includes(currentMonth)) suggestedPrice *= 1.05; else suggestedPrice *= 0.95;
                suggestedPrice *= (1 + (Math.random() * 0.06 - 0.03));
                document.getElementById('suggested_price').value = Math.round(suggestedPrice) + ' RWF (AI Suggested)';
            } else { document.getElementById('suggested_price').value = 'Market data not available'; }
        })
        .catch(()=>{ document.getElementById('suggested_price').value = 'Error getting suggestion'; });
}
function getMarketPrice() {
    var cropName = document.querySelector('[name="name"]').value || '';
    if (!cropName) return;
    fetch('market_price_api.php?commodity=' + encodeURIComponent(cropName))
        .then(response => response.json())
        .then(data => {
            if (data && data.price) {
                var marketInfo = data.price + ' RWF';
                if (data.market) marketInfo += ' (' + data.market + ')';
                if (data.source) marketInfo += ' - ' + data.source;
                if (data.date) marketInfo += ' - ' + data.date;
                document.getElementById('market_price').value = marketInfo;
            } else { document.getElementById('market_price').value = 'No recent market price found'; }
        })
        .catch(()=>{ document.getElementById('market_price').value = 'Error fetching market price'; });
}
</script> 