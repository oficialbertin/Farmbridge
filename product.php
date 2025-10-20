<?php
require 'db.php';
require 'session_helper.php';

// Allow public access to product details

$productId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($productId <= 0) {
	header('Location: crops.php');
	exit;
}

$stmt = $conn->prepare("SELECT c.*, u.name as farmer_name, u.phone as farmer_phone, u.profile_pic, u.id as farmer_user_id
						 FROM crops c JOIN users u ON c.farmer_id = u.id WHERE c.id = ?");
$stmt->bind_param('i', $productId);
$stmt->execute();
$product = $stmt->get_result()->fetch_assoc();
if (!$product) {
	header('Location: crops.php');
	exit;
}

include 'header.php';
?>
<main class="container mt-5">
	<a href="crops.php" class="btn btn-link mb-3">&larr; Back to Marketplace</a>

	<div class="row g-4">
		<div class="col-12 col-lg-6">
			<div class="card shadow-sm border-0">
				<?php if ($product['image']): ?>
					<img src="<?= htmlspecialchars($product['image']) ?>" class="card-img-top" style="height:380px;object-fit:cover;" alt="<?= htmlspecialchars($product['name']) ?>">
				<?php else: ?>
					<div class="card-img-top bg-light d-flex align-items-center justify-content-center" style="height:380px;">
						<i class="bi bi-image text-muted" style="font-size:3rem"></i>
					</div>
				<?php endif; ?>
			</div>
		</div>
		<div class="col-12 col-lg-6">
			<div class="card shadow-sm border-0 h-100">
				<div class="card-body d-flex flex-column">
					<h3 class="mb-1"><?= htmlspecialchars(ucwords($product['name'])) ?></h3>
					<div class="text-muted mb-2">Listed <?= date('M d, Y', strtotime($product['listed_at'])) ?></div>
					<div class="d-flex align-items-center gap-2 mb-3">
						<?php if (!empty($product['profile_pic'])): ?>
							<img src="<?= htmlspecialchars($product['profile_pic']) ?>" class="rounded-circle" style="width:30px;height:30px;object-fit:cover;">
						<?php else: ?>
							<span class="rounded-circle bg-light d-inline-flex align-items-center justify-content-center" style="width:30px;height:30px;"><i class="bi bi-person"></i></span>
						<?php endif; ?>
						<a href="farmer_profile.php?id=<?= (int)$product['farmer_user_id'] ?>" class="text-decoration-none">By <?= htmlspecialchars($product['farmer_name']) ?></a>
					</div>

					<p class="text-muted mb-3"><?= htmlspecialchars($product['description'] ?: 'No description provided.') ?></p>

					<div class="row g-3 mb-3">
						<div class="col-6">
							<div class="small text-muted">Price</div>
							<div class="fs-5 fw-semibold text-success"><?= number_format($product['price'], 0) ?> RWF/<?= htmlspecialchars($product['unit']) ?></div>
						</div>
						<div class="col-6">
							<div class="small text-muted">Available</div>
							<div class="fs-6 fw-semibold"><?= number_format($product['quantity']) ?> <?= htmlspecialchars($product['unit']) ?></div>
						</div>
					</div>

					<div class="row g-3 mb-3">
						<div class="col-12">
							<div id="aiPriceBox" class="alert alert-light border">
								<div class="d-flex justify-content-between align-items-center">
									<div>
										<div class="small text-muted">Market aggregated price</div>
										<div id="aiPriceValue" class="fw-semibold">Loading...</div>
										<div id="aiPriceMeta" class="small text-muted"></div>
									</div>
									<button id="aiTrendBtn" type="button" class="btn btn-sm btn-outline-primary">View 7d trend</button>
								</div>
								<div id="aiTrend" class="mt-2 small"></div>
							</div>
						</div>

					<?php if ($product['harvest_type'] === 'future'): ?>
						<div class="alert alert-info">
							<i class="bi bi-clock"></i>
							<strong>Future Harvest:</strong> This crop will be harvested on order.
							Estimated harvest date: <?= $product['estimated_harvest_date'] ? date('M d, Y', strtotime($product['estimated_harvest_date'])) : 'TBD' ?>
						</div>
					<?php endif; ?>

					<div class="mt-auto">
						<?php if ($product['status'] === 'available' && $product['quantity'] > 0): ?>
							<?php if (isset($_SESSION['user_id']) && $_SESSION['role'] === 'buyer'): ?>
								<form method="POST" action="checkout.php" class="d-flex gap-2">
									<input type="hidden" name="crop_id" value="<?= (int)$product['id'] ?>">
									<input type="number" name="order_quantity" class="form-control" min="1" max="<?= (int)$product['quantity'] ?>" placeholder="Qty" required>
									<button type="submit" class="btn btn-success w-auto"><i class="bi bi-cart-plus"></i> Order</button>
								</form>
							<?php else: ?>
								<div class="d-flex gap-2">
									<input type="number" class="form-control" min="1" max="<?= (int)$product['quantity'] ?>" placeholder="Qty" disabled>
									<button type="button" class="btn btn-success w-auto" onclick="showLoginPrompt()"><i class="bi bi-cart-plus"></i> Order</button>
								</div>
								<div class="alert alert-info mt-2"><i class="bi bi-info-circle"></i> Please login or register as a buyer to place an order.</div>
							<?php endif; ?>
						<?php else: ?>
							<span class="badge bg-secondary">Not available</span>
						<?php endif; ?>
					</div>
				</div>
			</div>
		</div>
	</div>

	<div class="mt-4">
		<h5>More from this farmer</h5>
		<div class="row g-3">
			<?php
			$stmt = $conn->prepare("SELECT id, name, image, price, unit FROM crops WHERE farmer_id = ? AND id <> ? ORDER BY listed_at DESC LIMIT 4");
			$stmt->bind_param('ii', $product['farmer_id'], $productId);
			$stmt->execute();
			$more = $stmt->get_result();
			if ($more && $more->num_rows > 0):
				while ($m = $more->fetch_assoc()):
					$mimg = $m['image'] ? htmlspecialchars($m['image']) : 'assets/logo.png';
			?>
				<div class="col-6 col-md-3">
					<a href="product.php?id=<?= (int)$m['id'] ?>" class="text-decoration-none">
						<div class="card h-100 shadow-sm border-0">
							<img src="<?= $mimg ?>" class="card-img-top" style="height:130px;object-fit:cover;">
							<div class="card-body p-2">
								<div class="small fw-semibold mb-1"><?= htmlspecialchars(ucwords($m['name'])) ?></div>
								<div class="small text-success"><?= number_format($m['price'], 0) ?> RWF/<?= htmlspecialchars($m['unit']) ?></div>
							</div>
						</div>
					</a>
				</div>
			<?php endwhile; else: ?>
				<div class="col-12"><div class="text-muted small">No more products from this farmer.</div></div>
			<?php endif; ?>
		</div>
	</div>

</main>

<script>
(function(){
	const crop = <?= json_encode($product['name']) ?>;
	function formBody(obj){ return new URLSearchParams(obj).toString(); }
	function getPrice(){
		return fetch('ai_service_bridge.php', {
			method:'POST',
			headers:{'Content-Type':'application/x-www-form-urlencoded'},
			body: formBody({action:'get_price', crop: crop})
		}).then(r=>r.json());
	}
	function getTrend(days){
		return fetch('ai_service_bridge.php', {
			method:'POST',
			headers:{'Content-Type':'application/x-www-form-urlencoded'},
			body: formBody({action:'get_trend', crop: crop, days: days})
		}).then(r=>r.json());
	}
	const priceEl = document.getElementById('aiPriceValue');
	const metaEl = document.getElementById('aiPriceMeta');
	const trendBtn = document.getElementById('aiTrendBtn');
	const trendEl = document.getElementById('aiTrend');

	getPrice().then(res=>{
		if(res && res.success){
			const d = res.data;
			priceEl.textContent = (d.aggregated_price != null ? (Number(d.aggregated_price).toLocaleString() + ' RWF/kg') : 'N/A');
			metaEl.textContent = 'Confidence: ' + (d.confidence ?? 0) + ' • Sources: ' + (d.source_count ?? 0);
		}else{
			priceEl.textContent = 'N/A';
			metaEl.textContent = (res && res.error) ? res.error : 'No data';
		}
	}).catch(()=>{ priceEl.textContent='N/A'; metaEl.textContent='Error'; });

	trendBtn.addEventListener('click', ()=>{
		trendEl.textContent = 'Loading trend...';
		getTrend(7).then(res=>{
			if(res && res.success){
				const d = res.data;
				if (d && d.trend && d.trend.length){
					const lines = d.trend.map(p=> (new Date(p.date)).toLocaleDateString() + ': ' + Number(p.price).toLocaleString() + ' RWF');
					trendEl.textContent = lines.join(' | ');
				}else{
					trendEl.textContent = 'No trend data';
				}
			}else{
				trendEl.textContent = (res && res.error) ? res.error : 'No trend data';
			}
		}).catch(()=>{ trendEl.textContent='Error loading trend'; });
	});
})();

function showLoginPrompt() {
    if (confirm('You need to login or register to place an order. Would you like to login now?')) {
        window.location.href = 'login.php';
    }
}
</script>

<?php include 'footer.php'; ?>


