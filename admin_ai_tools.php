<?php
require 'db.php';
require 'session_helper.php';
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
	header('Location: login.php');
	exit;
}
include 'header.php';
?>
<main class="container mt-5">
	<h2>AI Tools</h2>
	<a href="admin.php" class="btn btn-link mb-3">&larr; Back to Dashboard</a>
    <div class="card mb-4">
        <div class="card-body">
            <h5 class="card-title">AI Price & Trends</h5>
            <form id="ai-price-form" class="row g-3">
                <div class="col-md-6">
                    <label for="crop" class="form-label">Crop Name</label>
                    <input type="text" class="form-control" id="crop" name="crop" placeholder="e.g., tomato" required>
                </div>
                <div class="col-md-6">
                    <label for="days" class="form-label">Trend Days</label>
                    <input type="number" class="form-control" id="days" name="days" value="30" min="7" max="90" required>
                </div>
                <div class="col-12">
                    <button type="submit" class="btn btn-success">Analyze</button>
                </div>
            </form>
            <div id="ai-price-result" class="mt-4"></div>
        </div>
    </div>
</main>
<?php include 'footer.php'; ?>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<link rel="stylesheet" href="css/style.css">
<script>
document.getElementById('ai-price-form').onsubmit = function(e) {
	e.preventDefault();
	var crop = document.getElementById('crop').value;
    var days = parseInt(document.getElementById('days').value, 10) || 30;
	var resultDiv = document.getElementById('ai-price-result');
    resultDiv.innerHTML = '<div class="alert alert-info">Analyzing...</div>';
    // Use existing PHP bridge to avoid external dependencies
    const fd = new URLSearchParams();
    fd.set('action', 'get_price');
    fd.set('crop', crop);
    fetch('ai_service_bridge.php', { method: 'POST', body: fd })
      .then(r => r.json())
      .then(price => {
        const fd2 = new URLSearchParams();
        fd2.set('action', 'get_trend');
        fd2.set('crop', crop);
        fd2.set('days', days.toString());
        return Promise.all([Promise.resolve(price), fetch('ai_service_bridge.php', { method: 'POST', body: fd2 }).then(r=>r.json())]);
      })
      .then(([price, trend]) => {
        if (!price || price.success !== true) { throw new Error('price'); }
        if (!trend || trend.success !== true) { throw new Error('trend'); }
        const p = price.data;
        const t = trend.data;
        let html = '<div class="alert alert-success">';
        html += 'Aggregated price for <b>' + crop + '</b>: <b>' + (p.aggregated_price ?? p.mean_price ?? 'N/A') + ' RWF/kg</b>';
        if (p.confidence) html += ' <small>(conf ' + p.confidence + ')</small>';
        html += '</div>';
        if (t && t.trend && t.trend.length) {
            html += '<div class="card"><div class="card-body">';
            html += '<h6>Trend (' + t.period_days + 'd): ' + t.trend_direction + ' (' + (t.trend_percentage ?? 0) + '%)</h6>';
            html += '<div class="small text-muted">Points: ' + t.data_points + '</div>';
            html += '</div></div>';
        }
        resultDiv.innerHTML = html;
      })
      .catch(() => {
        resultDiv.innerHTML = '<div class="alert alert-danger">Analysis failed. Ensure Python is set up and try again.</div>';
      });
};
</script>