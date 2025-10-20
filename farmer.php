<?php require 'db.php';
require 'session_helper.php'; if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'farmer') { header('Location: login.php'); exit; } include 'header.php'; ?>
<main class="container mt-5">
    <div class="mb-4">
        <h2>Welcome, <?= htmlspecialchars($_SESSION['name']) ?>!</h2>
        <p class="lead">Farmer Dashboard</p>
        <?php
        // Personalized tip: most sold crop
        $farmer_id = $_SESSION['user_id'];
        $top = $conn->query("SELECT crops.name, SUM(cs.quantity) as total_qty FROM crop_sales cs JOIN crops ON cs.crop_id = crops.id WHERE crops.farmer_id = $farmer_id GROUP BY crops.name ORDER BY total_qty DESC LIMIT 1");
        if ($top && $row = $top->fetch_assoc()) {
            echo '<div class="alert alert-success mb-2">Your top selling crop: <b>' . htmlspecialchars($row['name']) . '</b> (' . $row['total_qty'] . ' sold)</div>';
        }
        // Tip: high demand crop
        $demand = $conn->query("SELECT crop_name FROM demand_forecast WHERE period='next_week' ORDER BY forecast_value DESC LIMIT 1");
        if ($demand && $row = $demand->fetch_assoc()) {
            echo '<div class="alert alert-info mb-2">Tip: <b>' . htmlspecialchars($row['crop_name']) . '</b> is expected to be in high demand next week. Consider listing more!</div>';
        }
        ?>
    </div>
    <!-- Quick Stats Row -->
    <div class="row g-4 mb-4">
        <div class="col-md-4">
            <div class="card text-center shadow-sm">
                <div class="card-body">
                    <i class="bi bi-basket display-5 text-success mb-2"></i>
                    <h6 class="card-title">Total Crops Listed</h6>
                    <div class="fs-3 fw-bold">
                        <?php
                        $res = $conn->query("SELECT COUNT(*) as cnt FROM crops WHERE farmer_id = $farmer_id");
                        $row = $res ? $res->fetch_assoc() : ['cnt'=>0];
                        echo $row['cnt'];
                        ?>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card text-center shadow-sm">
                <div class="card-body">
                    <i class="bi bi-currency-exchange display-5 text-success mb-2"></i>
                    <h6 class="card-title">Total Sales</h6>
                    <div class="fs-3 fw-bold">
                        <?php
                        $res = $conn->query("SELECT SUM(price) as total FROM crop_sales WHERE farmer_id = $farmer_id");
                        $row = $res ? $res->fetch_assoc() : ['total'=>0];
                        echo number_format($row['total'] ?? 0, 0) . ' RWF';
                        ?>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card text-center shadow-sm">
                <div class="card-body">
                    <i class="bi bi-cash-coin display-5 text-success mb-2"></i>
                    <h6 class="card-title">Pending Payments</h6>
                    <div class="fs-3 fw-bold">
                        <?php
                        $res = $conn->query("SELECT SUM(payments.amount) as pending FROM payments JOIN orders ON payments.order_id = orders.id JOIN crops ON orders.crop_id = crops.id WHERE crops.farmer_id = $farmer_id AND payments.status = 'pending'");
                        $row = $res ? $res->fetch_assoc() : ['pending'=>0];
                        echo number_format($row['pending'] ?? 0, 0) . ' RWF';
                        ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="row g-4">
        <div class="col-md-6 col-lg-4">
            <div class="card h-100 shadow-sm">
                <div class="card-body">
                    <h5 class="card-title text-success"><i class="bi bi-graph-up"></i> Demand Forecast</h5>
                    <?php
                    $forecast = $conn->query("SELECT crop_name, forecast_value FROM demand_forecast WHERE period='next_week' ORDER BY forecast_value DESC LIMIT 5");
                    if ($forecast && $forecast->num_rows > 0) {
                        echo '<ul class="list-group list-group-flush">';
                        while ($row = $forecast->fetch_assoc()) {
                            echo '<li class="list-group-item d-flex justify-content-between align-items-center">'
                                . htmlspecialchars($row['crop_name']) .
                                '<span class="badge bg-success rounded-pill">' . $row['forecast_value'] . ' expected orders</span></li>';
                        }
                        echo '</ul>';
                    } else {
                        echo '<div class="alert alert-info">No forecast data available yet.</div>';
                    }
                    ?>
                </div>
            </div>
        </div>
        <div class="col-md-6 col-lg-4">
            <div class="card h-100 shadow-sm">
                <div class="card-body">
                    <h5 class="card-title text-primary"><i class="bi bi-lightbulb"></i> Market Insights</h5>
                    <?php
                    $pop = $conn->query("SELECT crops.name, SUM(cs.quantity) as total_qty FROM crop_sales cs JOIN crops ON cs.crop_id = crops.id WHERE cs.sale_date >= CURDATE() - INTERVAL 30 DAY GROUP BY crops.name ORDER BY total_qty DESC LIMIT 1");
                    if ($pop && $row = $pop->fetch_assoc()) {
                        echo '<div class="alert alert-info mb-2">Most popular crop this month: <b>' . htmlspecialchars($row['name']) . '</b> (' . $row['total_qty'] . ' sold)</div>';
                    }
                    $inc = $conn->query("SELECT commodity, MAX(price) - MIN(price) as diff FROM market_prices WHERE date >= CURDATE() - INTERVAL 30 DAY GROUP BY commodity ORDER BY diff DESC LIMIT 1");
                    if ($inc) {
                        $row = $inc->fetch_assoc();
                        if ($row && $row['diff'] > 0) {
                            echo '<div class="alert alert-success mb-2">Highest price increase: <b>' . htmlspecialchars($row['commodity']) . '</b> (+' . number_format($row['diff'], 0) . ' RWF)</div>';
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
                    $crops = $conn->query("SELECT name FROM crops WHERE farmer_id = $farmer_id");
                    if ($crops && $crops->num_rows > 0) {
                        $alerts = 0;
                        while ($crop = $crops->fetch_assoc()) {
                            $name = $conn->real_escape_string($crop['name']);
                            $recent = $conn->query("SELECT MAX(price) as maxp, MIN(price) as minp FROM market_prices WHERE commodity LIKE '%$name%' AND date >= CURDATE() - INTERVAL 7 DAY");
                            if ($recent) {
                                $row = $recent->fetch_assoc();
                                if ($row && $row['minp'] > 0) {
                                    $increase = $row['maxp'] - $row['minp'];
                                    $percent = $increase / $row['minp'] * 100;
                                    if ($percent >= 20) {
                                        echo '<div class="alert alert-warning">Price spike alert: <b>' . htmlspecialchars($name) . '</b> market price increased by ' . number_format($percent, 1) . '% in the last week!</div>';
                                        $alerts++;
                                    }
                                }
                            }
                        }
                        if ($alerts === 0) {
                            echo '<div class="alert alert-info">No price spike alerts for your crops this week.</div>';
                        }
                    } else {
                        echo '<div class="alert alert-info">You have not listed any crops yet.</div>';
                    }
                    ?>
                </div>
            </div>
        </div>
    </div>
    <!-- Recent Activity Section -->
    <div class="row g-4 mt-4">
        <div class="col-md-12">
            <div class="card shadow-sm">
                <div class="card-body">
                    <h5 class="card-title"><i class="bi bi-clock-history"></i> Recent Activity</h5>
                    <?php
                    $recent = $conn->query("SELECT cs.sale_date, crops.name, cs.quantity, cs.price FROM crop_sales cs JOIN crops ON cs.crop_id = crops.id WHERE cs.farmer_id = $farmer_id ORDER BY cs.sale_date DESC LIMIT 5");
                    if ($recent && $recent->num_rows > 0) {
                        echo '<ul class="list-group list-group-flush">';
                        while ($row = $recent->fetch_assoc()) {
                            echo '<li class="list-group-item">'
                                . '<b>' . htmlspecialchars($row['name']) . '</b> - Sold ' . $row['quantity'] . ' for ' . number_format($row['price'], 0) . ' RWF on ' . $row['sale_date']
                                . '</li>';
                        }
                        echo '</ul>';
                    } else {
                        echo '<div class="alert alert-info">No recent sales activity.</div>';
                    }
                    ?>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Orders Section -->
    <div class="row g-4 mt-4">
        <div class="col-md-12">
            <div class="card shadow-sm">
                <div class="card-body">
                    <h5 class="card-title"><i class="bi bi-receipt"></i> Recent Orders</h5>
                    <?php
                    $orders = $conn->query("
                        SELECT o.*, c.name AS crop_name, c.unit, c.image, u.name as buyer_name
                        FROM orders o 
                        JOIN crops c ON o.crop_id = c.id 
                        JOIN users u ON o.buyer_id = u.id
                        WHERE c.farmer_id = $farmer_id 
                        ORDER BY o.created_at DESC 
                        LIMIT 10
                    ");
                    if ($orders && $orders->num_rows > 0) {
                        echo '<div class="row">';
                        while ($order = $orders->fetch_assoc()) {
                            $status_color = $order['status'] === 'completed' ? 'success' : ($order['status'] === 'paid' ? 'primary' : 'warning');
                            echo '<div class="col-md-6 mb-3">';
                            echo '<div class="card h-100">';
                            if ($order['image']) {
                                echo '<img src="' . htmlspecialchars($order['image']) . '" class="card-img-top" style="height:120px;object-fit:cover;" alt="Crop Image">';
                            }
                            echo '<div class="card-body">';
                            echo '<h6 class="card-title">' . htmlspecialchars($order['crop_name']) . '</h6>';
                            echo '<p class="card-text"><strong>Buyer:</strong> ' . htmlspecialchars($order['buyer_name']) . '</p>';
                            echo '<p class="card-text"><strong>Quantity:</strong> ' . $order['quantity'] . ' ' . htmlspecialchars($order['unit']) . '</p>';
                            echo '<p class="card-text"><strong>Total:</strong> ' . number_format($order['total'], 0) . ' RWF</p>';
                            echo '<p class="card-text"><strong>Status:</strong> <span class="badge bg-' . $status_color . '">' . ucfirst($order['status']) . '</span></p>';
                            echo '<p class="card-text"><strong>Delivery:</strong> ' . ucfirst($order['delivery_status']) . '</p>';
                            echo '<p class="card-text"><small>Ordered: ' . date('M d, Y', strtotime($order['created_at'])) . '</small></p>';
                            echo '<div class="mt-2">';
                            echo '<a href="order_details.php?id=' . $order['id'] . '" class="btn btn-outline-primary btn-sm">View Details</a>';
                            if ($order['status'] === 'paid' && !$order['confirmation_farmer']) {
                                echo '<a href="order_details.php?id=' . $order['id'] . '#confirm" class="btn btn-success btn-sm ms-1">Confirm Order</a>';
                            }
                            if ($order['confirmation_farmer'] && $order['delivery_status'] === 'farmer_confirmed') {
                                echo '<a href="order_details.php?id=' . $order['id'] . '#delivery" class="btn btn-info btn-sm ms-1">Mark Out for Delivery</a>';
                            }
                            echo '</div>';
                            echo '</div></div></div>';
                        }
                        echo '</div>';
                    } else {
                        echo '<div class="alert alert-info">No orders received yet.</div>';
                    }
                    ?>
                </div>
            </div>
        </div>
    </div>
</main>
<?php include 'footer.php'; ?>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<link rel="stylesheet" href="css/style.css"> 