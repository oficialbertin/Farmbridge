<?php include 'header.php'; ?>
<?php
$isLoggedIn = isset($_SESSION['user_id']);
$role = $_SESSION['role'] ?? null;
if ($isLoggedIn) {
    require_once 'db.php';
}
?>
<style>
.hero-section {
    background: linear-gradient(90deg, #e8f5e9 60%, #fff 100%);
    border-radius: 18px;
    box-shadow: 0 4px 32px rgba(56,142,60,0.08);
    padding: 3rem 2rem 2rem 2rem;
    margin-bottom: 2.5rem;
    position: relative;
    overflow: hidden;
}
.hero-img {
    max-height: 340px;
    border-radius: 18px;
    box-shadow: 0 4px 24px rgba(56,142,60,0.10);
    object-fit: cover;
    width: 100%;
}
.hero-btns .btn {
    margin-right: 1rem;
    margin-bottom: 0.5rem;
}
.feature-card {
    border: none;
    border-radius: 18px;
    box-shadow: 0 2px 12px rgba(56,142,60,0.07);
    transition: transform 0.2s;
}
.feature-card:hover {
    transform: translateY(-6px) scale(1.03);
    box-shadow: 0 8px 32px rgba(56,142,60,0.13);
}
.feature-icon {
    font-size: 2.8rem;
    color: #388e3c;
    margin-bottom: 0.7rem;
}
@media (max-width: 768px) {
    .hero-section { padding: 1.5rem 0.5rem; }
    .hero-img { max-height: 180px; }
}
</style>
<main class="container mt-5">
    <?php if (!$isLoggedIn): ?>
    <section class="hero-section row align-items-center justify-content-center mb-5">
        <div class="col-md-6 text-center text-md-start mb-4 mb-md-0">
            <h1 class="display-4 fw-bold mb-3 text-success">Welcome to FarmBridge AI Rwanda</h1>
            <p class="lead mb-4">Empowering Rwandan farmers and buyers with smart, AI-driven market insights, secure payments, and direct connections. Join us to transform agriculture for everyone!</p>
            <div class="hero-btns mb-3">
                <a href="register.php" class="btn btn-success btn-lg px-4 shadow-sm"><i class="bi bi-person-plus"></i> Get Started</a>
                <a href="#features" class="btn btn-outline-success btn-lg px-4"><i class="bi bi-lightbulb"></i> Learn More</a>
            </div>
            <div class="d-flex align-items-center mt-3">
                <i class="bi bi-shield-check text-success me-2"></i>
                <span class="text-muted">Trusted by farmers, buyers, and partners across Rwanda</span>
            </div>
        </div>
        <div class="col-md-6 text-center">
            <img src="uploads/68874c5582ade_tomatoes.jpg" alt="FarmBridge Hero" class="hero-img">
        </div>
    </section>
    <section id="features" class="mb-5">
        <div class="row text-center mb-4">
            <div class="col-12">
                <h2 class="fw-bold text-success mb-2">Why Choose FarmBridge AI?</h2>
                <p class="lead text-muted">A smarter, fairer, and more connected agricultural marketplace for Rwanda</p>
            </div>
        </div>
        <div class="row g-4">
            <div class="col-md-4">
                <div class="card feature-card h-100">
                    <div class="card-body text-center">
                        <i class="bi bi-people feature-icon"></i>
                        <h5 class="card-title">For Farmers</h5>
                        <p class="card-text">List crops, track payments, and access AI-powered price suggestions. Reach more buyers and get paid faster.</p>
                        <a href="register.php" class="btn btn-outline-success btn-sm mt-2">Join as Farmer</a>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card feature-card h-100">
                    <div class="card-body text-center">
                        <i class="bi bi-basket feature-icon"></i>
                        <h5 class="card-title">For Buyers</h5>
                        <p class="card-text">Browse fresh produce, place orders, and connect directly with local farmers. Enjoy transparency and quality.</p>
                        <a href="register.php" class="btn btn-outline-success btn-sm mt-2">Join as Buyer</a>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card feature-card h-100">
                    <div class="card-body text-center">
                        <i class="bi bi-cpu feature-icon"></i>
                        <h5 class="card-title">AI & Secure Payments</h5>
                        <p class="card-text">Get smart price and demand forecasts, and enjoy secure, instant payments with MTN Mobile Money integration.</p>
                        <a href="#" class="btn btn-outline-success btn-sm mt-2">See How It Works</a>
                    </div>
                </div>
            </div>
        </div>
    </section>
    <?php else: ?>
    <!-- Logged-in Personalized Home -->
    <section class="mb-4">
        <div class="row align-items-center g-3">
            <div class="col-12 col-lg-8">
                <div class="p-4 rounded-3" style="background:linear-gradient(90deg,#e8f5e9 60%, #fff 100%);border:1px solid #e9ecef">
                    <h2 class="fw-bold text-success mb-1">Hi <?= htmlspecialchars($_SESSION['name'] ?? 'there') ?>, welcome back!</h2>
                    <div class="text-muted">Here are quick actions and highlights tailored for you.</div>
                    <div class="d-flex flex-wrap gap-2 mt-3">
                        <?php if ($role === 'buyer'): ?>
                            <a href="crops.php" class="btn btn-success"><i class="bi bi-shop"></i> Marketplace</a>
                            <a href="buyer_orders.php" class="btn btn-outline-success"><i class="bi bi-bag-check"></i> My Orders</a>
                            <a href="buyer_payments.php" class="btn btn-outline-success"><i class="bi bi-credit-card"></i> Payments</a>
                            <a href="buyer_disputes.php" class="btn btn-outline-success"><i class="bi bi-exclamation-diamond"></i> Disputes</a>
                        <?php elseif ($role === 'farmer'): ?>
                            <a href="farmer_add_crop.php" class="btn btn-success"><i class="bi bi-plus-circle"></i> List New Crop</a>
                            <a href="farmer_crops.php" class="btn btn-outline-success"><i class="bi bi-basket"></i> My Crops</a>
                            <a href="farmer_orders.php" class="btn btn-outline-success"><i class="bi bi-bag-check"></i> Orders</a>
                            <a href="farmer_payments.php" class="btn btn-outline-success"><i class="bi bi-cash"></i> Payments</a>
                        <?php elseif ($role === 'admin'): ?>
                            <a href="admin_users.php" class="btn btn-success"><i class="bi bi-people"></i> Users</a>
                            <a href="admin_crops.php" class="btn btn-outline-success"><i class="bi bi-flower2"></i> Crops</a>
                            <a href="admin_orders.php" class="btn btn-outline-success"><i class="bi bi-bag"></i> Orders</a>
                            <a href="admin_payments.php" class="btn btn-outline-success"><i class="bi bi-cash-stack"></i> Payments</a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <div class="col-12 col-lg-4">
                <div class="p-4 rounded-3 h-100" style="background:#f8fdf9;border:1px solid #e9ecef">
                    <div class="fw-semibold mb-2"><i class="bi bi-graph-up-arrow text-success"></i> Quick Stats</div>
                    <div class="row g-2 small">
                        <?php if ($role === 'buyer'):
                            $buyerId = (int)$_SESSION['user_id'];
                            $counts = [
                                'pending' => 0,'paid' => 0,'completed' => 0
                            ];
                            $q = $conn->query("SELECT status, COUNT(*) cnt FROM orders WHERE buyer_id = $buyerId GROUP BY status");
                            if ($q) { while ($r = $q->fetch_assoc()) { $counts[$r['status']] = (int)$r['cnt']; } }
                        ?>
                        <div class="col-4"><div class="p-2 border rounded text-center"><div class="fw-bold text-success"><?= $counts['pending'] ?></div><div class="text-muted">Pending</div></div></div>
                        <div class="col-4"><div class="p-2 border rounded text-center"><div class="fw-bold text-success"><?= $counts['paid'] ?></div><div class="text-muted">Paid</div></div></div>
                        <div class="col-4"><div class="p-2 border rounded text-center"><div class="fw-bold text-success"><?= $counts['completed'] ?></div><div class="text-muted">Completed</div></div></div>
                        <?php elseif ($role === 'farmer'):
                            $farmerId = (int)$_SESSION['user_id'];
                            $c1 = $conn->query("SELECT COUNT(*) c FROM crops WHERE farmer_id = $farmerId AND status='available'")->fetch_assoc()['c'] ?? 0;
                            $c2 = $conn->query("SELECT COUNT(*) c FROM crops WHERE farmer_id = $farmerId AND status='sold'")->fetch_assoc()['c'] ?? 0;
                            $c3 = 0;
                            $o = $conn->query("SELECT COUNT(*) c FROM orders o JOIN crops c ON o.crop_id = c.id WHERE c.farmer_id = $farmerId AND o.status IN ('pending','paid')");
                            if ($o) { $row = $o->fetch_assoc(); $c3 = (int)($row['c'] ?? 0); }
                        ?>
                        <div class="col-4"><div class="p-2 border rounded text-center"><div class="fw-bold text-success"><?= (int)$c1 ?></div><div class="text-muted">Available</div></div></div>
                        <div class="col-4"><div class="p-2 border rounded text-center"><div class="fw-bold text-success"><?= (int)$c2 ?></div><div class="text-muted">Sold</div></div></div>
                        <div class="col-4"><div class="p-2 border rounded text-center"><div class="fw-bold text-success"><?= (int)$c3 ?></div><div class="text-muted">Active Orders</div></div></div>
                        <?php elseif ($role === 'admin'): ?>
                        <div class="col-6"><div class="p-2 border rounded text-center"><div class="fw-bold text-success"><?= (int)($conn->query("SELECT COUNT(*) c FROM users")->fetch_assoc()['c'] ?? 0) ?></div><div class="text-muted">Users</div></div></div>
                        <div class="col-6"><div class="p-2 border rounded text-center"><div class="fw-bold text-success"><?= (int)($conn->query("SELECT COUNT(*) c FROM crops")->fetch_assoc()['c'] ?? 0) ?></div><div class="text-muted">Crops</div></div></div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <?php if ($role === 'buyer'): ?>
    <!-- Featured for Buyers -->
    <section class="mb-5">
        <div class="d-flex justify-content-between align-items-center mb-2">
            <h4 class="mb-0">Featured Crops</h4>
            <a href="crops.php" class="btn btn-outline-success btn-sm">View all</a>
        </div>
        <div class="row g-4">
            <?php
            $res = $conn->query("SELECT c.id, c.name, c.image, c.price, c.unit, u.name as farmer_name FROM crops c JOIN users u ON c.farmer_id=u.id WHERE c.status='available' AND c.quantity>0 ORDER BY c.listed_at DESC LIMIT 8");
            if ($res && $res->num_rows>0): while($it=$res->fetch_assoc()): $img = $it['image'] ?: 'assets/logo.png'; ?>
            <div class="col-6 col-md-4 col-lg-3">
                <a href="product.php?id=<?= (int)$it['id'] ?>" class="text-decoration-none">
                    <div class="card h-100 shadow-sm border-0">
                        <img src="<?= htmlspecialchars($img) ?>" class="card-img-top" style="height:140px;object-fit:cover;">
                        <div class="card-body p-2">
                            <div class="fw-semibold small mb-1"><?= htmlspecialchars(ucwords($it['name'])) ?></div>
                            <div class="d-flex justify-content-between small">
                                <span class="text-success fw-semibold"><?= number_format($it['price'],0) ?> RWF/<?= htmlspecialchars($it['unit']) ?></span>
                                <span class="text-muted">By <?= htmlspecialchars($it['farmer_name']) ?></span>
                            </div>
                        </div>
                    </div>
                </a>
            </div>
            <?php endwhile; else: ?>
            <div class="col-12"><div class="text-muted">No featured crops yet.</div></div>
            <?php endif; ?>
        </div>
    </section>
    <?php elseif ($role === 'farmer'): ?>
    <!-- Quick Tips for Farmers -->
    <section class="mb-5">
        <h4 class="mb-3">Farmer Tips</h4>
        <div class="row g-3">
            <div class="col-12 col-lg-4"><div class="p-3 border rounded-3 h-100"><div class="fw-semibold mb-1"><i class="bi bi-currency-exchange text-success"></i> Pricing</div><div class="text-muted small">Set competitive prices using Market Insights and recent sales averages.</div></div></div>
            <div class="col-12 col-lg-4"><div class="p-3 border rounded-3 h-100"><div class="fw-semibold mb-1"><i class="bi bi-image text-success"></i> Photos</div><div class="text-muted small">Add clear photos to increase buyer trust and conversions.</div></div></div>
            <div class="col-12 col-lg-4"><div class="p-3 border rounded-3 h-100"><div class="fw-semibold mb-1"><i class="bi bi-truck text-success"></i> Delivery</div><div class="text-muted small">Offer farmer delivery to earn more orders and better ratings.</div></div></div>
        </div>
    </section>
    <?php elseif ($role === 'admin'): ?>
    <!-- Admin Shortcuts -->
    <section class="mb-5">
        <h4 class="mb-3">Admin Shortcuts</h4>
        <div class="d-flex flex-wrap gap-2">
            <a href="admin_users.php" class="btn btn-outline-success"><i class="bi bi-people"></i> Manage Users</a>
            <a href="admin_crops.php" class="btn btn-outline-success"><i class="bi bi-flower2"></i> Manage Crops</a>
            <a href="admin_orders.php" class="btn btn-outline-success"><i class="bi bi-bag"></i> Manage Orders</a>
            <a href="admin_payments.php" class="btn btn-outline-success"><i class="bi bi-cash-stack"></i> Manage Payments</a>
        </div>
    </section>
    <?php endif; ?>
    <?php endif; ?>
    <section class="mb-5">
        <div class="row g-4 align-items-center">
            <div class="col-md-6">
                <img src="uploads/688883ccba006_carrots.jpg" alt="Carrots" class="img-fluid rounded shadow-sm" style="max-height:220px;object-fit:cover;">
            </div>
            <div class="col-md-6">
                <h3 class="fw-bold text-success mb-2">How FarmBridge AI Works</h3>
                <ul class="list-unstyled lead">
                    <li class="mb-2"><i class="bi bi-check-circle text-success me-2"></i> Register as a farmer or buyer</li>
                    <li class="mb-2"><i class="bi bi-check-circle text-success me-2"></i> List or browse crops and place orders</li>
                    <li class="mb-2"><i class="bi bi-check-circle text-success me-2"></i> Get AI-powered price and demand suggestions</li>
                    <li class="mb-2"><i class="bi bi-check-circle text-success me-2"></i> Pay and get paid instantly and securely</li>
                </ul>
                <a href="register.php" class="btn btn-success btn-lg mt-2"><i class="bi bi-person-plus"></i> Get Started Now</a>
            </div>
    </div>
    </section>
</main>
<?php include 'footer.php'; ?>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<link rel="stylesheet" href="css/style.css"> 