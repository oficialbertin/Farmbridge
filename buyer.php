<?php
require 'db.php';
require 'session_helper.php';
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'buyer') {
    header('Location: login.php');
    exit;
}
if (empty($_SESSION['csrf_token'])) { $_SESSION['csrf_token'] = bin2hex(random_bytes(32)); }
$msg = '';

// Ensure we have some market data for insights/alerts if DB is empty
try {
    $chk = $conn->query("SELECT COUNT(*) c FROM market_prices WHERE date >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)");
    $row = $chk ? $chk->fetch_assoc() : null;
    if (!$row || (int)$row['c'] === 0) {
        $seed = [
            ['maize','Kigali',450,'Local Market'],['maize','Musanze',420,'Local Market'],
            ['tomato','Kigali',1200,'Local Market'],['tomato','Musanze',1100,'Local Market'],
            ['potato','Kigali',800,'Local Market'],['potato','Musanze',750,'Local Market'],
            ['banana','Kigali',600,'Local Market'],['rice','Kigali',1800,'Local Market'],
            ['bean','Kigali',2200,'Local Market'],['cassava','Huye',300,'Local Market']
        ];
        $stmtSeed = $conn->prepare("INSERT INTO market_prices (commodity, market, price, date, source) VALUES (?, ?, ?, CURDATE(), ?)");
        foreach ($seed as $s) { $stmtSeed->bind_param('ssds', $s[0], $s[1], $s[2], $s[3]); $stmtSeed->execute(); }
        $stmtSeed->close();
    }
} catch (Throwable $e) { /* ignore */ }
// Handle order submission - redirect to checkout
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['order_crop_id'])) {
    $token = $_POST['csrf_token'] ?? '';
    if (!hash_equals($_SESSION['csrf_token'], $token)) {
        $msg = '<div class="alert alert-danger">Security check failed. Please try again.</div>';
    } else {
        $crop_id = (int)$_POST['order_crop_id'];
        $qty = isset($_POST['order_quantity']) ? (int)$_POST['order_quantity'] : 1;
        if ($qty < 1) { $qty = 1; }
        header('Location: checkout.php?crop_id=' . $crop_id . '&qty=' . $qty);
        exit;
    }
}
include 'header.php';
?>
<main class="container mt-5">
    <div class="mb-4">
        <h2>Welcome, <?= htmlspecialchars($_SESSION['name']) ?>!</h2>
        <?php
        // Personalized tip: most ordered crop
        $buyer_id = (int)$_SESSION['user_id'];
        $row = null;
        if ($stmt = $conn->prepare("SELECT crops.name, COUNT(*) as cnt FROM orders JOIN crops ON orders.crop_id = crops.id WHERE orders.buyer_id = ? GROUP BY crops.name ORDER BY cnt DESC LIMIT 1")) {
            $stmt->bind_param("i", $buyer_id);
            $stmt->execute();
            $res = $stmt->get_result();
            if ($res) { $row = $res->fetch_assoc(); }
            $stmt->close();
        }
        if ($row) {
            echo '<div class="alert alert-success mb-2">Your favorite crop: <b>' . htmlspecialchars($row['name']) . '</b> (' . $row['cnt'] . ' orders)</div>';
        }
        // Tip: trending crop
        $row = null;
        if ($stmt = $conn->prepare("SELECT crops.name FROM crop_sales cs JOIN crops ON cs.crop_id = crops.id WHERE cs.sale_date >= CURDATE() - INTERVAL 30 DAY GROUP BY crops.name ORDER BY SUM(cs.quantity) DESC LIMIT 1")) {
            $stmt->execute();
            $res = $stmt->get_result();
            if ($res) { $row = $res->fetch_assoc(); }
            $stmt->close();
        }
        if ($row) {
            echo '<div class="alert alert-info mb-2">Tip: <b>' . htmlspecialchars($row['name']) . '</b> is trending this month. Check it out in the marketplace!</div>';
        }
        ?>
    </div>
    <div class="row g-4">
        <div class="col-md-6 col-lg-4">
            <div class="card h-100 shadow-sm">
                <div class="card-body">
                    <h5 class="card-title text-success"><i class="bi bi-stars"></i> Recommended for You</h5>
                    <div class="row">
                    <?php
                    $buyer_id = (int)$_SESSION['user_id'];
                    $rec = null;
                    if ($stmt = $conn->prepare("SELECT crops.*, COUNT(orders.id) as order_count FROM orders JOIN crops ON orders.crop_id = crops.id WHERE orders.buyer_id = ? GROUP BY crops.id ORDER BY order_count DESC LIMIT 2")) {
                        $stmt->bind_param("i", $buyer_id);
                        $stmt->execute();
                        $rec = $stmt->get_result();
                        $stmt->close();
                    }
                    $shown = [];
                    if ($rec && $rec->num_rows > 0) {
                        while ($crop = $rec->fetch_assoc()) {
                            $shown[] = $crop['id'];
                            echo '<div class="col-12 mb-2">';
                            echo '<div class="card border-success">';
                            if ($crop['image']) {
                                echo '<img src="' . htmlspecialchars($crop['image']) . '" class="card-img-top" style="height:120px;object-fit:cover;" alt="Crop Image">';
                            }
                            echo '<div class="card-body">';
                            echo '<h6 class="card-title">' . htmlspecialchars($crop['name']) . '</h6>';
                            echo '<form method="post" class="mt-2">';
                            echo '<input type="hidden" name="csrf_token" value="' . htmlspecialchars($_SESSION['csrf_token']) . '">';
                            echo '<input type="hidden" name="order_crop_id" value="' . $crop['id'] . '">';
                            echo '<div class="input-group mb-2">';
                            echo '<input type="number" name="order_quantity" class="form-control" min="1" max="' . $crop['quantity'] . '" placeholder="Quantity" required>';
                            echo '<button type="submit" class="btn btn-success">Order</button>';
                            echo '</div>';
                            echo '</form>';
                            echo '</div></div></div>';
                        }
                    }
                    $trending = null;
                    if ($stmt = $conn->prepare("SELECT crops.*, SUM(cs.quantity) as sold_qty FROM crop_sales cs JOIN crops ON cs.crop_id = crops.id WHERE cs.sale_date >= CURDATE() - INTERVAL 30 DAY GROUP BY crops.id ORDER BY sold_qty DESC LIMIT 3")) {
                        $stmt->execute();
                        $trending = $stmt->get_result();
                        $stmt->close();
                    }
                    if ($trending && $trending->num_rows > 0) {
                        while ($crop = $trending->fetch_assoc()) {
                            if (in_array($crop['id'], $shown)) continue;
                            echo '<div class="col-12 mb-2">';
                            echo '<div class="card border-info">';
                            if ($crop['image']) {
                                echo '<img src="' . htmlspecialchars($crop['image']) . '" class="card-img-top" style="height:120px;object-fit:cover;" alt="Crop Image">';
                            }
                            echo '<div class="card-body">';
                            echo '<h6 class="card-title">' . htmlspecialchars($crop['name']) . '</h6>';
                            echo '<form method="post" class="mt-2">';
                            echo '<input type="hidden" name="csrf_token" value="' . htmlspecialchars($_SESSION['csrf_token']) . '">';
                            echo '<input type="hidden" name="order_crop_id" value="' . $crop['id'] . '">';
                            echo '<div class="input-group mb-2">';
                            echo '<input type="number" name="order_quantity" class="form-control" min="1" max="' . $crop['quantity'] . '" placeholder="Quantity" required>';
                            echo '<button type="submit" class="btn btn-success">Order</button>';
                            echo '</div>';
                            echo '</form>';
                            echo '</div></div></div>';
                        }
                    }
                    if (empty($shown)) {
                        echo '<div class="alert alert-info">No personalized recommendations yet. Order crops to get recommendations!</div>';
                    }
                    ?>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-6 col-lg-4">
            <div class="card h-100 shadow-sm">
                <div class="card-body">
                    <h5 class="card-title text-primary"><i class="bi bi-lightbulb"></i> Market Insights</h5>
                    <?php
                    // Popular crop by sales
                    if ($stmt = $conn->prepare("SELECT crops.name, SUM(cs.quantity) as total_qty FROM crop_sales cs JOIN crops ON cs.crop_id = crops.id WHERE cs.sale_date >= CURDATE() - INTERVAL 30 DAY GROUP BY crops.name ORDER BY total_qty DESC LIMIT 1")) {
                        $stmt->execute(); $pop = $stmt->get_result(); $row = $pop ? $pop->fetch_assoc() : null; $stmt->close();
                        if ($row) {
                            echo '<div class="alert alert-info mb-2">Most popular this month: <b>' . htmlspecialchars($row['name']) . '</b> (' . (int)$row['total_qty'] . ' sold)</div>';
                        }
                    }
                    // Biggest price increase
                    if ($stmt = $conn->prepare("SELECT commodity, (MAX(price) - MIN(price)) AS diff FROM market_prices WHERE date >= CURDATE() - INTERVAL 30 DAY GROUP BY commodity ORDER BY diff DESC LIMIT 1")) {
                        $stmt->execute(); $inc = $stmt->get_result(); $incRow = $inc ? $inc->fetch_assoc() : null; $stmt->close();
                        if ($incRow && (float)$incRow['diff'] > 0) {
                            echo '<div class="alert alert-success mb-2">Highest price increase: <b>' . htmlspecialchars($incRow['commodity']) . '</b> (+' . number_format((float)$incRow['diff'], 0) . ' RWF)</div>';
                        } else {
                            echo '<div class="alert alert-light">Market looks stable this month.</div>';
                        }
                    }
                    ?>
                </div>
            </div>
        </div>
        <div class="col-md-12 col-lg-4">
            <div class="card h-100 shadow-sm">
                <div class="card-body">
                    <h5 class="card-title text-danger"><i class="bi bi-bell"></i> Smart Alerts</h5>
                    <?php
                    $fav = null;
                    if ($stmt = $conn->prepare("SELECT crops.name FROM orders JOIN crops ON orders.crop_id = crops.id WHERE orders.buyer_id = ? GROUP BY crops.name ORDER BY COUNT(*) DESC LIMIT 3")) { $stmt->bind_param('i', $buyer_id); $stmt->execute(); $fav = $stmt->get_result(); $stmt->close(); }
                    $fav_names = [];
                    if ($fav && $fav->num_rows > 0) {
                        while ($row = $fav->fetch_assoc()) {
                            $fav_names[] = $conn->real_escape_string($row['name']);
                        }
                    }
                    $alerts = 0;
                    if (!empty($fav_names)) {
                        // New listings for favorites
                        $placeholders = implode(',', array_fill(0, count($fav_names), '?'));
                        $types = str_repeat('s', count($fav_names));
                        $new = null;
                        if ($stmt = $conn->prepare("SELECT name FROM crops WHERE name IN ($placeholders) AND listed_at >= CURDATE() - INTERVAL 3 DAY")) {
                            $stmt->bind_param($types, ...$fav_names);
                            $stmt->execute();
                            $new = $stmt->get_result();
                            $stmt->close();
                        }
                        if ($new && $new->num_rows > 0) {
                            while ($row = $new->fetch_assoc()) {
                                echo '<div class="alert alert-info">New <b>' . htmlspecialchars($row['name']) . '</b> listed in the last 3 days!</div>';
                                $alerts++;
                            }
                        }
                        foreach ($fav_names as $name) {
                            if ($stmt = $conn->prepare("SELECT MAX(price) as maxp, MIN(price) as minp FROM market_prices WHERE commodity LIKE ? AND date >= CURDATE() - INTERVAL 7 DAY")) {
                                $like = "%$name%"; $stmt->bind_param('s', $like); $stmt->execute(); $recent = $stmt->get_result();
                            } else { $recent = false; }
                            if ($recent && $row = $recent->fetch_assoc()) {
                                if ($row && isset($row['maxp']) && $row['maxp'] > 0) {
                                    $drop = $row['maxp'] - $row['minp'];
                                    $percent = $drop / $row['maxp'] * 100;
                                    if ($percent >= 10) {
                                        echo '<div class="alert alert-success">Price drop alert: <b>' . htmlspecialchars($name) . '</b> market price dropped by ' . number_format($percent, 1) . '% in the last week!</div>';
                                        $alerts++;
                                    }
                                }
                            }
                            if (isset($stmt) && $stmt) { $stmt->close(); }
                        }
                    }
                    if ($alerts === 0) {
                        echo '<div class="alert alert-info">No new alerts yet — follow your favorites to see updates here.</div>';
                    }
                    ?>
                </div>
            </div>
        </div>
    </div>
    <!-- Available Crops Section -->
    <hr class="my-5">
    <h2>Available Crops</h2>
    <div class="row">
    <?php
    $result = null;
    if ($stmt = $conn->prepare("SELECT crops.*, users.name AS farmer_name FROM crops JOIN users ON crops.farmer_id = users.id WHERE crops.status='available' AND crops.quantity > 0 ORDER BY crops.listed_at DESC")) {
        $stmt->execute();
        $result = $stmt->get_result();
        $stmt->close();
    }
    if ($result && $result->num_rows > 0) {
        while ($crop = $result->fetch_assoc()) {
            echo '<div class="col-md-4 mb-3">';
            echo '<div class="card">';
            if ($crop['image']) {
                echo '<img src="' . htmlspecialchars($crop['image']) . '" class="card-img-top" style="height:180px;object-fit:cover;" alt="Crop Image">';
            }
            echo '<div class="card-body">';
            echo '<h5 class="card-title">' . htmlspecialchars($crop['name']) . '</h5>';
            echo '<p class="card-text">' . htmlspecialchars($crop['description']) . '</p>';
            echo '<p class="card-text"><strong>Farmer:</strong> ' . htmlspecialchars($crop['farmer_name']) . '</p>';
            echo '<p class="card-text"><strong>Available:</strong> ' . $crop['quantity'] . ' ' . htmlspecialchars($crop['unit']) . '</p>';
            echo '<p class="card-text"><strong>Price:</strong> ' . $crop['price'] . ' RWF</p>';
            echo '<form method="post" class="mt-2">';
            echo '<input type="hidden" name="csrf_token" value="' . htmlspecialchars($_SESSION['csrf_token']) . '">';
            echo '<input type="hidden" name="order_crop_id" value="' . $crop['id'] . '">';
            echo '<div class="input-group mb-2">';
            echo '<input type="number" name="order_quantity" class="form-control" min="1" max="' . $crop['quantity'] . '" placeholder="Quantity" required>';
            echo '<button type="submit" class="btn btn-success">Order</button>';
            echo '</div>';
            echo '</form>';
            echo '</div></div></div>';
        }
    } else {
        echo '<div class="alert alert-info">No crops available at the moment.</div>';
    }
    ?>
    </div>
    <!-- Orders Section -->
    <hr class="my-5">
    <h2>Your Orders</h2>
    <div id="order-list">
    <?php
    $buyer_id = (int)$_SESSION['user_id'];
    $result = null;
    if ($stmt = $conn->prepare("SELECT o.*, c.name AS crop_name, c.unit, c.image, u.name as farmer_name FROM orders o JOIN crops c ON o.crop_id = c.id JOIN users u ON c.farmer_id = u.id WHERE o.buyer_id = ? ORDER BY o.created_at DESC")) {
        $stmt->bind_param("i", $buyer_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $stmt->close();
    }
    if ($result && $result->num_rows > 0) {
        echo '<div class="row">';
        while ($order = $result->fetch_assoc()) {
            $status_color = $order['status'] === 'completed' ? 'success' : ($order['status'] === 'paid' ? 'primary' : 'warning');
            echo '<div class="col-md-6 mb-3">';
            echo '<div class="card h-100">';
            if ($order['image']) {
                echo '<img src="' . htmlspecialchars($order['image']) . '" class="card-img-top" style="height:120px;object-fit:cover;" alt="Crop Image">';
            }
            echo '<div class="card-body">';
            echo '<h5 class="card-title">' . htmlspecialchars($order['crop_name']) . '</h5>';
            echo '<p class="card-text"><strong>Farmer:</strong> ' . htmlspecialchars($order['farmer_name']) . '</p>';
            echo '<p class="card-text"><strong>Quantity:</strong> ' . $order['quantity'] . ' ' . htmlspecialchars($order['unit']) . '</p>';
            echo '<p class="card-text"><strong>Total:</strong> ' . number_format($order['total'], 0) . ' RWF</p>';
            echo '<p class="card-text"><strong>Status:</strong> <span class="badge bg-' . $status_color . '">' . ucfirst($order['status']) . '</span></p>';
            echo '<p class="card-text"><strong>Delivery:</strong> ' . ucfirst($order['delivery_status']) . '</p>';
            echo '<p class="card-text"><small>Ordered: ' . date('M d, Y', strtotime($order['created_at'])) . '</small></p>';
            echo '<div class="mt-2">';
            echo '<a href="order_details.php?id=' . $order['id'] . '" class="btn btn-outline-primary btn-sm">View Details</a>';
            if ($order['status'] === 'paid' && $order['delivery_status'] === 'out_for_delivery' && !$order['confirmation_buyer']) {
                echo '<a href="order_details.php?id=' . $order['id'] . '#confirm" class="btn btn-success btn-sm ms-1">Confirm Delivery</a>';
            }
            echo '</div>';
            echo '</div></div></div>';
        }
        echo '</div>';
    } else {
        echo '<div class="alert alert-info">You have not placed any orders yet.</div>';
    }
    ?>
    </div>
</main>
<?php include 'footer.php'; ?>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<link rel="stylesheet" href="css/style.css"> 