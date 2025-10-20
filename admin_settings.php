<?php
require 'db.php';
require 'settings_helpers.php';
if (session_status() === PHP_SESSION_NONE) { require 'session_helper.php'; }
if (empty($_SESSION['csrf_token'])) { $_SESSION['csrf_token'] = bin2hex(random_bytes(32)); }

// Basic guard - adjust to your admin role logic
if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'admin') {
    header('Location: login.php');
    exit;
}

$saved = false; $error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = $_POST['csrf_token'] ?? '';
    if (!hash_equals($_SESSION['csrf_token'], $token)) {
        $error = 'Security check failed. Please try again.';
    } else {
    $delivery_enabled = isset($_POST['delivery_enabled']) ? 'true' : 'false';
    $delivery_mode = $_POST['delivery_mode'] ?? 'auto'; // auto|manual
    $delivery_base = isset($_POST['delivery_base']) ? (float)$_POST['delivery_base'] : 4000.0;
    $delivery_per_unit = isset($_POST['delivery_per_unit']) ? (float)$_POST['delivery_per_unit'] : 300.0;
    $min_qty = isset($_POST['min_quantity']) ? (int)$_POST['min_quantity'] : 1;
    $platform_markup_pct = isset($_POST['platform_markup_pct']) ? (float)$_POST['platform_markup_pct'] : 0.08;

    try {
        settings_set('delivery_enabled', $delivery_enabled);
        settings_set('delivery_mode', $delivery_mode);
        settings_set('delivery_base', (string)$delivery_base);
        settings_set('delivery_per_unit', (string)$delivery_per_unit);
        settings_set('min_quantity', (string)$min_qty);
        settings_set('platform_markup_pct', (string)$platform_markup_pct);
        $saved = true;
    } catch (Throwable $e) {
        $error = 'Failed to save: ' . $e->getMessage();
    }
    }
}

$delivery_enabled_val = settings_get_bool('delivery_enabled', true);
$delivery_mode_val = settings_get('delivery_mode', 'auto');
$delivery_base_val = settings_get_float('delivery_base', 4000.0);
$delivery_per_unit_val = settings_get_float('delivery_per_unit', 300.0);
$min_qty_val = settings_get_int('min_quantity', 1);
$platform_markup_pct_val = settings_get_float('platform_markup_pct', 0.08);

include 'header.php';
?>
<div class="container mt-5">
    <h2>Platform Settings</h2>
    <p>Configure delivery fees, modes, minimum order quantity, and platform markup.</p>
    <?php if ($saved): ?><div class="alert alert-success">Settings saved.</div><?php endif; ?>
    <?php if ($error): ?><div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div><?php endif; ?>
    <form class="form" method="post" style="max-width: 720px; margin: 0 auto; border: 1px solid #ddd; border-radius: 8px; padding: 16px; background: #fff;">
        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
        <div class="row mb-3">
            <div class="col-12">
                <label class="form-check-label"><input type="checkbox" class="form-check-input" name="delivery_enabled" <?php echo $delivery_enabled_val ? 'checked' : ''; ?>> Enable delivery fee</label>
            </div>
        </div>
        <div class="row mb-3">
            <div class="col-md-6">
                <label class="form-label">Delivery mode</label>
                <select class="form-select" name="delivery_mode">
                    <option value="auto" <?php echo $delivery_mode_val==='auto'?'selected':''; ?>>Automatic (base + per unit)</option>
                    <option value="manual" <?php echo $delivery_mode_val==='manual'?'selected':''; ?>>Manual (0 at checkout)</option>
                </select>
            </div>
        </div>
        <div class="row mb-3">
            <div class="col-md-6">
                <label class="form-label">Base fee (RWF)</label>
                <input type="number" step="1" class="form-control" name="delivery_base" value="<?php echo (int)$delivery_base_val; ?>">
            </div>
            <div class="col-md-6">
                <label class="form-label">Per unit (RWF)</label>
                <input type="number" step="1" class="form-control" name="delivery_per_unit" value="<?php echo (int)$delivery_per_unit_val; ?>">
            </div>
        </div>
        <div class="row mb-3">
            <div class="col-md-6">
                <label class="form-label">Minimum quantity</label>
                <input type="number" step="1" class="form-control" name="min_quantity" value="<?php echo (int)$min_qty_val; ?>">
            </div>
            <div class="col-md-6">
                <label class="form-label">Platform markup (%)</label>
                <input type="number" step="0.01" min="0" max="1" class="form-control" name="platform_markup_pct" value="<?php echo htmlspecialchars($platform_markup_pct_val, ENT_QUOTES); ?>">
            </div>
        </div>
        <div class="d-flex justify-content-end">
            <button class="btn btn-success" type="submit">Save Settings</button>
        </div>
    </form>
    <p class="mt-3"><a href="payments_status.php">View payments</a></p>
</div>
<?php include 'footer.php'; ?>


