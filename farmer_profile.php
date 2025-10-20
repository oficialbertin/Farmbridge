<?php
require 'db.php';
require 'session_helper.php';

$farmerId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($farmerId <= 0) {
    header('Location: crops.php');
    exit;
}

// Fetch farmer
$stmt = $conn->prepare("SELECT id, name, email, phone, profile_pic, created_at FROM users WHERE id = ?");
$stmt->bind_param('i', $farmerId);
$stmt->execute();
$farmer = $stmt->get_result()->fetch_assoc();
if (!$farmer) {
    header('Location: crops.php');
    exit;
}

// Fetch products
$stmt = $conn->prepare("SELECT id, name, description, quantity, unit, price, image, status, listed_at FROM crops WHERE farmer_id = ? ORDER BY listed_at DESC");
$stmt->bind_param('i', $farmerId);
$stmt->execute();
$products = $stmt->get_result();

include 'header.php';
?>
<main class="container mt-5">
  <a href="crops.php" class="btn btn-link mb-3">&larr; Back to Marketplace</a>

  <div class="card shadow-sm border-0 mb-4">
    <div class="card-body d-flex gap-3 align-items-center">
      <div>
        <?php if (!empty($farmer['profile_pic'])): ?>
          <img src="<?= htmlspecialchars($farmer['profile_pic']) ?>" class="rounded-circle" style="width:80px;height:80px;object-fit:cover;" alt="Farmer">
        <?php else: ?>
          <div class="rounded-circle bg-light d-flex align-items-center justify-content-center" style="width:80px;height:80px;"><i class="bi bi-person" style="font-size:2rem"></i></div>
        <?php endif; ?>
      </div>
      <div class="flex-grow-1">
        <h3 class="mb-1"><?= htmlspecialchars($farmer['name']) ?></h3>
        <div class="text-muted">Member since <?= date('M Y', strtotime($farmer['created_at'])) ?></div>
        <div class="small text-muted mt-1">
          <i class="bi bi-telephone"></i> <?= htmlspecialchars($farmer['phone'] ?: 'N/A') ?>
          &nbsp; &middot; &nbsp;
          <i class="bi bi-envelope"></i> <?= htmlspecialchars($farmer['email'] ?: 'N/A') ?>
        </div>
      </div>
      <div>
        <a href="#products" class="btn btn-success"><i class="bi bi-basket"></i> View Products</a>
      </div>
    </div>
  </div>

  <h4 id="products" class="mb-3">Products by <?= htmlspecialchars($farmer['name']) ?></h4>

  <div class="row g-4">
    <?php if ($products && $products->num_rows > 0): ?>
      <?php while ($p = $products->fetch_assoc()): 
        $img = $p['image'] ? htmlspecialchars($p['image']) : 'assets/logo.png';
        $statusBadge = $p['status'] === 'available' ? 'success' : ($p['status'] === 'pending' ? 'warning' : 'secondary');
      ?>
        <div class="col-12 col-sm-6 col-lg-4 col-xxl-3">
          <div class="card h-100 shadow-sm border-0">
            <div class="position-relative">
              <img src="<?= $img ?>" class="card-img-top" alt="<?= htmlspecialchars($p['name']) ?>" style="height:200px;object-fit:cover;">
              <span class="badge bg-<?= $statusBadge ?> position-absolute top-0 start-0 m-2 text-uppercase"><?= htmlspecialchars($p['status']) ?></span>
              <span class="badge bg-dark position-absolute top-0 end-0 m-2"><?= number_format($p['price'], 0) ?> RWF/<?= htmlspecialchars($p['unit']) ?></span>
            </div>
            <div class="card-body d-flex flex-column">
              <h5 class="card-title mb-1"><?= htmlspecialchars(ucwords($p['name'])) ?></h5>
              <div class="text-muted mb-2">Listed <?= date('M d, Y', strtotime($p['listed_at'])) ?></div>
              <p class="text-muted small mb-3" style="min-height:40px;">
                <?= htmlspecialchars($p['description'] ?: 'No description provided.') ?>
              </p>
              <div class="d-flex justify-content-between mb-3">
                <div>
                  <div class="small text-muted">Quantity</div>
                  <div class="fw-semibold"><?= number_format($p['quantity']) ?> <?= htmlspecialchars($p['unit']) ?></div>
                </div>
                <div>
                  <div class="small text-muted">Price</div>
                  <div class="fw-semibold text-success"><?= number_format($p['price'], 0) ?> RWF</div>
                </div>
              </div>
              <div class="mt-auto">
                <?php if ($p['status'] === 'available' && $p['quantity'] > 0): ?>
                  <form method="POST" action="checkout.php" class="d-flex gap-2">
                    <input type="hidden" name="crop_id" value="<?= (int)$p['id'] ?>">
                    <input type="number" name="order_quantity" class="form-control" min="1" max="<?= (int)$p['quantity'] ?>" placeholder="Qty" required>
                    <button type="submit" class="btn btn-success w-auto"><i class="bi bi-cart-plus"></i> Order</button>
                  </form>
                <?php else: ?>
                  <span class="badge bg-secondary">Not available</span>
                <?php endif; ?>
              </div>
            </div>
          </div>
        </div>
      <?php endwhile; else: ?>
        <div class="col-12"><div class="alert alert-info">This farmer has no listed products yet.</div></div>
    <?php endif; ?>
  </div>
</main>

<?php include 'footer.php'; ?>


