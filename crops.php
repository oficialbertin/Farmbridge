<?php
require 'db.php';
require 'session_helper.php';
// Allow public access to marketplace
include 'header.php';
?>
<main class="container mt-5">
	<div class="d-flex align-items-center justify-content-between mb-3">
		<div>
			<h2 class="mb-1">Marketplace</h2>
			<div class="text-muted">Discover fresh produce from trusted farmers</div>
		</div>
		<?php if (isset($_SESSION['user_id']) && $_SESSION['role'] === 'buyer'): ?>
			<a href="buyer.php" class="btn btn-link">&larr; Back to Dashboard</a>
		<?php else: ?>
			<a href="index.php" class="btn btn-link">&larr; Back to Home</a>
		<?php endif; ?>
	</div>

	<div class="mb-4 row g-2">
		<div class="col-12 col-md-6 col-lg-4">
			<input type="text" id="cropSearch" class="form-control" placeholder="Search by crop, farmer...">
		</div>
		<div class="col-6 col-md-3 col-lg-2">
			<select id="statusFilter" class="form-select">
				<option value="">All Statuses</option>
				<option value="available">Available</option>
				<option value="pending">Pending</option>
				<option value="sold">Sold</option>
			</select>
		</div>
		<div class="col-6 col-md-3 col-lg-2">
			<select id="sortBy" class="form-select">
				<option value="nearest">Nearest first</option>
				<option value="newest">Newest</option>
				<option value="price_asc">Price: Low to High</option>
				<option value="price_desc">Price: High to Low</option>
				<option value="qty_desc">Quantity: High to Low</option>
			</select>
		</div>
	</div>

	<div class="mb-3 row g-2 align-items-end">
		<div class="col-12 col-sm-4">
			<label class="form-label small">District</label>
			<input type="text" id="filterDistrict" class="form-control" placeholder="e.g., Kigali">
		</div>
		<div class="col-6 col-sm-4">
			<label class="form-label small">Sector</label>
			<input type="text" id="filterSector" class="form-control" placeholder="e.g., Gasabo">
		</div>
		<div class="col-6 col-sm-4">
			<label class="form-label small">Cell</label>
			<input type="text" id="filterCell" class="form-control" placeholder="e.g., Kimironko">
		</div>
	</div>

	<div id="marketGrid" class="row g-4">
		<?php
		$result = $conn->query("SELECT crops.*, users.name AS farmer_name, users.profile_pic FROM crops JOIN users ON crops.farmer_id = users.id ORDER BY crops.listed_at DESC");
		if ($result && $result->num_rows > 0):
			while ($crop = $result->fetch_assoc()):
				$img = $crop['image'] ? htmlspecialchars($crop['image']) : 'assets/logo.png';
				$statusBadge = $crop['status'] === 'available' ? 'success' : ($crop['status'] === 'pending' ? 'warning' : 'secondary');
		?>
		<div class="col-12 col-sm-6 col-lg-4 col-xxl-3">
			<div class="card h-100 shadow-sm border-0 product-card" 
				 data-name="<?= htmlspecialchars(strtolower($crop['name'])) ?>"
				 data-farmer="<?= htmlspecialchars(strtolower($crop['farmer_name'])) ?>"
				 data-status="<?= htmlspecialchars(strtolower($crop['status'])) ?>"
				 data-price="<?= (int)$crop['price'] ?>"
				 data-qty="<?= (int)$crop['quantity'] ?>"
				 data-time="<?= strtotime($crop['listed_at']) ?>"
				 data-district="<?= htmlspecialchars(strtolower($crop['district'] ?? '')) ?>"
				 data-sector="<?= htmlspecialchars(strtolower($crop['sector'] ?? '')) ?>"
				 data-cell="<?= htmlspecialchars(strtolower($crop['cell'] ?? '')) ?>">
				<div class="position-relative">
					<a href="product.php?id=<?= (int)$crop['id'] ?>">
						<img src="<?= $img ?>" class="card-img-top" alt="<?= htmlspecialchars($crop['name']) ?>" style="height:200px;object-fit:cover;">
					</a>
					<span class="badge bg-<?= $statusBadge ?> position-absolute top-0 start-0 m-2 text-uppercase"><?= htmlspecialchars($crop['status']) ?></span>
					<span class="badge bg-dark position-absolute top-0 end-0 m-2"><?= number_format($crop['price'], 0) ?> RWF/<?= htmlspecialchars($crop['unit']) ?></span>
				</div>
				<div class="card-body d-flex flex-column">
					<h5 class="card-title mb-1">
						<a class="text-decoration-none" href="product.php?id=<?= (int)$crop['id'] ?>">
							<?= htmlspecialchars(ucwords($crop['name'])) ?>
						</a>
					</h5>
					<div class="text-muted mb-2">Listed <?= date('M d, Y', strtotime($crop['listed_at'])) ?></div>
					<div class="d-flex align-items-center gap-2 mb-3">
						<?php if (!empty($crop['profile_pic'])): ?>
							<img src="<?= htmlspecialchars($crop['profile_pic']) ?>" alt="Farmer" class="rounded-circle" style="width:28px;height:28px;object-fit:cover;">
						<?php else: ?>
							<span class="rounded-circle bg-light d-inline-flex align-items-center justify-content-center" style="width:28px;height:28px;"><i class="bi bi-person"></i></span>
						<?php endif; ?>
						<a class="text-decoration-none" href="farmer_profile.php?id=<?= (int)$crop['farmer_id'] ?>">By <?= htmlspecialchars($crop['farmer_name']) ?></a>
					</div>
					<div class="d-flex justify-content-between mb-3">
						<div>
							<div class="small text-muted">Quantity</div>
							<div class="fw-semibold"><?= number_format($crop['quantity']) ?> <?= htmlspecialchars($crop['unit']) ?></div>
						</div>
						<div>
							<div class="small text-muted">Price</div>
							<div class="fw-semibold text-success"><?= number_format($crop['price'], 0) ?> RWF</div>
						</div>
					</div>
					<div class="mt-auto">
						<?php if ($crop['status'] === 'available' && $crop['quantity'] > 0): ?>
							<?php if (isset($_SESSION['user_id']) && $_SESSION['role'] === 'buyer'): ?>
								<form method="POST" action="checkout.php" class="d-flex gap-2">
									<input type="hidden" name="crop_id" value="<?= (int)$crop['id'] ?>">
									<input type="number" name="order_quantity" class="form-control" min="1" max="<?= (int)$crop['quantity'] ?>" placeholder="Qty" required>
									<button type="submit" class="btn btn-success w-auto"><i class="bi bi-cart-plus"></i> Order</button>
								</form>
							<?php else: ?>
								<div class="d-flex gap-2">
									<input type="number" class="form-control" min="1" max="<?= (int)$crop['quantity'] ?>" placeholder="Qty" disabled>
									<button type="button" class="btn btn-success w-auto" onclick="showLoginPrompt()"><i class="bi bi-cart-plus"></i> Order</button>
								</div>
							<?php endif; ?>
						<?php else: ?>
							<span class="badge bg-secondary">Not available</span>
						<?php endif; ?>
					</div>
				</div>
			</div>
		</div>
		<?php endwhile; else: ?>
			<div class="col-12"><div class="alert alert-info">No crops found.</div></div>
		<?php endif; ?>
	</div>
</main>

<style>
.product-card:hover { transform: translateY(-2px); box-shadow: 0 0.5rem 1rem rgba(0,0,0,.08)!important; transition: .2s; }
</style>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<link rel="stylesheet" href="css/style.css">
<script>
const searchInput = document.getElementById('cropSearch');
const statusFilter = document.getElementById('statusFilter');
const sortBy = document.getElementById('sortBy');
const grid = document.getElementById('marketGrid');
const fDistrict = document.getElementById('filterDistrict');
const fSector = document.getElementById('filterSector');
const fCell = document.getElementById('filterCell');

function matchLocation(card){
  const d = (fDistrict.value||'').toLowerCase().trim();
  const s = (fSector.value||'').toLowerCase().trim();
  const c = (fCell.value||'').toLowerCase().trim();
  const cd = card.getAttribute('data-district')||'';
  const cs = card.getAttribute('data-sector')||'';
  const cc = card.getAttribute('data-cell')||'';
  // Match if provided filters are contained
  if (d && !cd.includes(d)) return false;
  if (s && !cs.includes(s)) return false;
  if (c && !cc.includes(c)) return false;
  return true;
}

function applyFilters() {
  const q = (searchInput.value || '').toLowerCase();
  const status = (statusFilter.value || '').toLowerCase();
  const cards = Array.from(grid.children);
  cards.forEach(col => {
    const card = col.querySelector('.product-card');
    const name = card.getAttribute('data-name');
    const farmer = card.getAttribute('data-farmer');
    const st = card.getAttribute('data-status');
    const matchText = !q || name.includes(q) || farmer.includes(q);
    const matchStatus = !status || status === st;
    const matchLoc = matchLocation(card);
    col.style.display = (matchText && matchStatus && matchLoc) ? '' : 'none';
  });
}

function locationScore(card){
  // Higher score for more specific matches
  let score = 0;
  const d = (fDistrict.value||'').toLowerCase().trim();
  const s = (fSector.value||'').toLowerCase().trim();
  const c = (fCell.value||'').toLowerCase().trim();
  const cd = card.getAttribute('data-district')||'';
  const cs = card.getAttribute('data-sector')||'';
  const cc = card.getAttribute('data-cell')||'';
  if (d && cd.includes(d)) score += 1;
  if (s && cs.includes(s)) score += 1;
  if (c && cc.includes(c)) score += 1;
  return score;
}

function applySort() {
  const mode = sortBy.value;
  const cols = Array.from(grid.children);
  const getVal = (col, key) => parseFloat(col.querySelector('.product-card').getAttribute(key));
  cols.sort((a,b) => {
    if (mode === 'nearest') return locationScore(b.querySelector('.product-card')) - locationScore(a.querySelector('.product-card'));
    if (mode === 'price_asc') return getVal(a,'data-price') - getVal(b,'data-price');
    if (mode === 'price_desc') return getVal(b,'data-price') - getVal(a,'data-price');
    if (mode === 'qty_desc') return getVal(b,'data-qty') - getVal(a,'data-qty');
    return getVal(b,'data-time') - getVal(a,'data-time'); // newest
  });
  cols.forEach(c => grid.appendChild(c));
}

searchInput.addEventListener('input', () => { applyFilters(); });
statusFilter.addEventListener('change', () => { applyFilters(); });
sortBy.addEventListener('change', () => { applySort(); });
[fDistrict,fSector,fCell].forEach(el=> el.addEventListener('input', ()=>{ applyFilters(); applySort(); }));

// initial sort
applySort();

function showLoginPrompt() {
    if (confirm('You need to login or register to place an order. Would you like to login now?')) {
        window.location.href = 'login.php';
    }
}
</script>
<?php include 'footer.php'; ?>