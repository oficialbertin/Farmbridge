<?php
require 'db.php';
require_once __DIR__ . '/settings_helpers.php';
require 'session_helper.php';
if (empty($_SESSION['csrf_token'])) { $_SESSION['csrf_token'] = bin2hex(random_bytes(32)); }

// Check if user is logged in and is a buyer
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'buyer') {
	header('Location: login.php');
	exit;
}

// Get crop details from POST or GET parameter
$crop_id = 0;
if (isset($_POST['crop_id'])) { $crop_id = (int)$_POST['crop_id']; }
if (!$crop_id && isset($_GET['crop_id'])) { $crop_id = (int)$_GET['crop_id']; }
if (!$crop_id) {
	header('Location: buyer.php');
	exit;
}

// Fetch crop details
$stmt = $conn->prepare("
    SELECT c.*, u.name as farmer_name, u.phone as farmer_phone 
    FROM crops c 
    JOIN users u ON c.farmer_id = u.id 
    WHERE c.id = ? AND c.status = 'available'
");
$stmt->bind_param("i", $crop_id);
$stmt->execute();
$result = $stmt->get_result();
$crop = $result->fetch_assoc();

if (!$crop) {
	header('Location: buyer.php');
	exit;
}

// Fetch buyer details
$buyer_id = $_SESSION['user_id'];
$stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
$stmt->bind_param("i", $buyer_id);
$stmt->execute();
$buyer = $stmt->get_result()->fetch_assoc();

// Platform-wide markup percentage (e.g., 8%)
// Load configurable settings
$PLATFORM_MARKUP_PCT = function_exists('settings_get_float') ? settings_get_float('platform_markup_pct', 0.08) : 0.08;
$min_qty = function_exists('settings_get_int') ? settings_get_int('min_quantity', 1) : 1;

// Initial quantity passed from marketplace/product page
$initial_qty = 1;
if (isset($_POST['order_quantity'])) { $initial_qty = (int)$_POST['order_quantity']; }
elseif (isset($_GET['qty'])) { $initial_qty = (int)$_GET['qty']; }
if ($initial_qty < 1) { $initial_qty = 1; }

$unit_price_with_markup = (float)$crop['price'] * (1.0 + (float)$PLATFORM_MARKUP_PCT);

include 'header.php';
?>

<div class="container-fluid py-2 py-md-4">
	<div class="row g-3">
		<div class="col-12 col-lg-8">
			<div class="card shadow-sm border-0">
				<div class="card-header bg-success text-white">
					<h4 class="mb-0"><i class="bi bi-cart-check"></i> Checkout</h4>
				</div>
				<div class="card-body">
					<!-- Order Summary -->
					<div class="row mb-4 g-3">
						<div class="col-12 col-md-4">
							<?php if ($crop['image']): ?>
								<img src="<?= htmlspecialchars($crop['image']) ?>" alt="<?= htmlspecialchars($crop['name']) ?>" class="img-fluid rounded w-100" style="max-height: 200px; object-fit: cover;">
							<?php else: ?>
								<div class="bg-light rounded d-flex align-items-center justify-content-center" style="height: 200px;">
									<i class="bi bi-image text-muted" style="font-size: 3rem;"></i>
								</div>
							<?php endif; ?>
						</div>
						<div class="col-12 col-md-8">
							<h5 class="text-success"><?= htmlspecialchars($crop['name']) ?></h5>
							<p class="text-muted mb-2"><?= htmlspecialchars($crop['description']) ?></p>
							<div class="row">
								<div class="col-6">
									<small class="text-muted">Price per <?= $crop['unit'] ?> (incl. platform markup):</small><br>
									<strong class="text-success"><?= number_format($unit_price_with_markup, 0) ?> RWF</strong>
								</div>
								<div class="col-6">
									<small class="text-muted">Available:</small><br>
									<strong><?= $crop['quantity'] ?> <?= $crop['unit'] ?></strong>
								</div>
							</div>
							<?php if ($crop['harvest_type'] === 'future'): ?>
								<div class="alert alert-info mt-2">
									<i class="bi bi-clock"></i>
									<strong>Future Harvest:</strong> This crop will be harvested on order. 
									Estimated delivery: <?= $crop['estimated_harvest_date'] ? date('M d, Y', strtotime($crop['estimated_harvest_date'])) : 'TBD' ?>
								</div>
							<?php endif; ?>
						</div>
					</div>

					<!-- Checkout Form -->
                    <form id="checkoutForm" method="POST" action="process_order.php">
                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
						<input type="hidden" name="crop_id" value="<?= $crop_id ?>">
						<input type="hidden" name="farmer_id" value="<?= $crop['farmer_id'] ?>">
						<input type="hidden" name="unit_price_with_markup" value="<?= (float)$unit_price_with_markup ?>">
                        <input type="hidden" name="delivery_option" value="buyer">

						<!-- Quantity Selection -->
						<div class="mb-3">
                            <label class="form-label fw-bold">Quantity (<?= $crop['unit'] ?>)</label>
                            <input type="number" name="quantity" id="quantity" class="form-control" 
                                   min="1" max="<?= $crop['quantity'] ?>" value="<?= max((int)$min_qty, min((int)$crop['quantity'], (int)$initial_qty)) ?>" required
                                   onchange="calculateTotal()">
                            <div class="form-text">Minimum: <?= (int)$min_qty ?>, Maximum available: <?= $crop['quantity'] ?> <?= $crop['unit'] ?></div>
						</div>

						<!-- Delivery Location: Province -> District -> Sector -->
						<div class="mb-3">
							<label class="form-label fw-bold">Delivery Location (Rwanda)</label>
							<div class="row g-2 mb-2">
								<div class="col-12 col-sm-4">
									<select id="province" class="form-select" required>
										<option value="">Select Province</option>
									</select>
								</div>
								<div class="col-12 col-sm-4">
									<select id="district" class="form-select" required disabled>
										<option value="">Select District</option>
									</select>
								</div>
								<div class="col-12 col-sm-4">
									<select id="sector" class="form-select" required disabled>
										<option value="">Select Sector</option>
									</select>
								</div>
							</div>
							<input type="hidden" name="province_id" id="province_id">
							<input type="hidden" name="district_id" id="district_id">
							<input type="hidden" name="sector_id" id="sector_id">
							<input type="text" class="form-control" name="address_details" placeholder="Nearby landmark / street (optional)">
							<input type="text" class="form-control mt-2" name="delivery_phone" placeholder="Contact phone" value="<?= htmlspecialchars($buyer['phone'] ?? '') ?>" required>
							<small class="text-muted">We deliver from the farmer to your address. Delivery fee is auto-estimated.</small>
						</div>

						<!-- Estimated Delivery Date -->
						<div class="mb-3">
							<label class="form-label fw-bold">Estimated Delivery</label>
							<div class="alert alert-light">
								<?php 
								$delivery_days = $crop['harvest_type'] === 'future' ? 7 : 3;
								$estimated_date = date('M d, Y', strtotime("+{$delivery_days} days"));
								?>
								<i class="bi bi-calendar-check"></i>
								<strong><?= $estimated_date ?></strong>
								<?php if ($crop['harvest_type'] === 'future'): ?>
									<br><small class="text-muted">(Includes harvest time)</small>
								<?php endif; ?>
							</div>
						</div>

						<!-- Buyer Notes -->
						<div class="mb-3">
							<label class="form-label">Special Instructions (Optional)</label>
							<textarea name="buyer_notes" class="form-control" rows="3" 
								  placeholder="Any special requirements or notes for the farmer..."></textarea>
						</div>

						<!-- Escrow Information -->
						<div class="alert alert-success">
							<h6><i class="bi bi-shield-check"></i> Secure Payment with Escrow</h6>
							<p class="mb-0 small">
								Your payment will be held securely by FarmBridge AI until you confirm delivery. 
								This protects both you and the farmer, ensuring a fair transaction.
							</p>
						</div>

                        <!-- Order Summary -->
						<div class="card bg-light">
							<div class="card-body">
								<h6 class="card-title">Order Summary</h6>
								<div class="row">
									<div class="col-6">Product Price:</div>
									<div class="col-6 text-end" id="productPrice">0 RWF</div>
								</div>
                                <?php $delivery_enabled = function_exists('settings_get_bool') ? settings_get_bool('delivery_enabled', true) : true; ?>
                                <?php if ($delivery_enabled): ?>
                                <div class="row" id="deliveryFeeRow">
                                    <div class="col-6">Delivery Fee (auto):</div>
                                    <div class="col-6 text-end" id="deliveryFee">0 RWF</div>
                                </div>
                                <?php endif; ?>
								<hr>
								<div class="row fw-bold">
									<div class="col-6">Total:</div>
									<div class="col-6 text-end text-success" id="totalPrice">0 RWF</div>
								</div>
							</div>
						</div>

						<!-- Payment Method -->
						<div class="mb-3">
							<label class="form-label fw-bold">Payment Method</label>
							<div class="alert alert-primary">
								<i class="bi bi-phone"></i>
								<strong>MTN Mobile Money</strong><br>
								<small class="text-muted">
									Payment will be processed via MTN MoMo to: <?= substr($buyer['phone'], 0, 3) ?>****<?= substr($buyer['phone'], -3) ?>
								</small>
							</div>
						</div>

						<!-- Submit Button -->
						<div class="d-grid">
							<button type="submit" class="btn btn-success btn-lg">
								<i class="bi bi-lock"></i> Pay Securely with MTN MoMo
							</button>
						</div>
					</form>
				</div>
			</div>
		</div>
	</div>
</div>

<script>
const UNIT_PRICE = <?= (float)$unit_price_with_markup ?>;
const MIN_QTY = <?= (int)$min_qty ?>;
const DELIVERY_ENABLED = <?= $delivery_enabled ? 'true' : 'false' ?>;
async function fetchJSON(url){ const r = await fetch(url); return r.json(); }
async function loadProvinces(){
	const items = await fetchJSON('api/provinces.php?lang=en');
	const s = document.getElementById('province'); s.innerHTML = '<option value="">Province</option>';
	items.forEach(i => s.innerHTML += `<option value="${i.id}">${i.name}</option>`);
}
async function loadDistricts(pid){
	const d = document.getElementById('district');
	const c = document.getElementById('sector');
	if(!pid){ 
		d.innerHTML='<option value="">Select District</option>'; 
		c.innerHTML='<option value="">Select Sector</option>';
		d.disabled = true;
		c.disabled = true;
		return; 
	}
	const items = await fetchJSON(`api/districts.php?province_id=${pid}&lang=en`);
	d.innerHTML = '<option value="">Select District</option>';
	items.forEach(i => d.innerHTML += `<option value="${i.id}">${i.name}</option>`);
	d.disabled = false;
	c.innerHTML = '<option value="">Select Sector</option>';
	c.disabled = true;
}
async function loadSectors(did){
	const s = document.getElementById('sector');
	if(!did){ 
		s.innerHTML='<option value="">Select Sector</option>'; 
		s.disabled = true;
		return; 
	}
	const items = await fetchJSON(`api/sectors.php?district_id=${did}&lang=en`);
	s.innerHTML = '<option value="">Select Sector</option>';
	items.forEach(i => s.innerHTML += `<option value="${i.id}">${i.name}</option>`);
	s.disabled = false;
}
function estimateDeliveryFee(){
    if (!DELIVERY_ENABLED) return 0;
    const qty = parseInt(document.getElementById('quantity').value)||0;
    return (qty > 0 ? (4000 + 300 * qty) : 0);
}
function calculateTotal() {
    let quantity = parseInt(document.getElementById('quantity').value) || 0;
    if (quantity < MIN_QTY) { quantity = MIN_QTY; document.getElementById('quantity').value = MIN_QTY; }
    const product = quantity * UNIT_PRICE;
    const delivery = estimateDeliveryFee();
    const productEl = document.getElementById('productPrice');
    const deliveryEl = document.getElementById('deliveryFee');
    const totalEl = document.getElementById('totalPrice');
    if (productEl) productEl.textContent = product.toLocaleString() + ' RWF';
    if (deliveryEl) deliveryEl.textContent = delivery.toLocaleString() + ' RWF';
    if (totalEl) totalEl.textContent = (product + delivery).toLocaleString() + ' RWF';
}
// keep hidden ids in sync
['province','district','sector'].forEach(id=>{
	document.addEventListener('change', (e)=>{
		if(e.target && e.target.id===id){
			document.getElementById(id+'_id').value = e.target.value || '';
		}
	});
});
document.getElementById('province').addEventListener('change', e => loadDistricts(e.target.value));
document.getElementById('district').addEventListener('change', e => loadSectors(e.target.value));
loadProvinces();
calculateTotal();
</script>

<?php include 'footer.php'; ?>