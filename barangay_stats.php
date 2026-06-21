<?php
include 'conn.php';

$vtown = trim($_GET['vtown'] ?? $_POST['vtown'] ?? '');

// ── Pole breakdown per barangay+icon ──────────────────────────────────────────
$where  = "WHERE icon IN ('ppole.png','spole.png','upole.png')";
$params = [];
if (!empty($vtown)) {
    $where .= " AND town = ?";
    $params[] = $vtown;
}
$bd_stmt = sqlsrv_query($conn,
    "SELECT brgy, icon, COUNT(*) AS cnt FROM tbl_elements $where GROUP BY brgy, icon",
    $params);

$pole_breakdown = [];
if ($bd_stmt) {
    while ($row = sqlsrv_fetch_array($bd_stmt, SQLSRV_FETCH_ASSOC)) {
        $brgy = $row['brgy'] ?: 'Unknown';
        $pole_breakdown[$brgy][$row['icon']] = (int)$row['cnt'];
    }
}

// ── Transformer count per barangay ────────────────────────────────────────────
$tw     = "WHERE icon = 'xformer.png'" . (!empty($vtown) ? " AND town = ?" : "");
$tp     = !empty($vtown) ? [$vtown] : [];
$t_stmt = sqlsrv_query($conn,
    "SELECT brgy, COUNT(*) AS cnt FROM tbl_elements $tw GROUP BY brgy", $tp);

$trn_counts = [];
if ($t_stmt) {
    while ($row = sqlsrv_fetch_array($t_stmt, SQLSRV_FETCH_ASSOC)) {
        $brgy = $row['brgy'] ?: 'Unknown';
        $trn_counts[$brgy] = (int)$row['cnt'];
    }
}

// ── Barangay list ─────────────────────────────────────────────────────────────
$fw     = !empty($vtown) ? "WHERE town = ? AND brgy IS NOT NULL AND brgy != ''" : "WHERE brgy IS NOT NULL AND brgy != ''";
$fp     = !empty($vtown) ? [$vtown] : [];
$f_stmt = sqlsrv_query($conn,
    "SELECT DISTINCT brgy FROM tbl_elements $fw ORDER BY brgy", $fp);

$barangays = [];
if ($f_stmt) {
    while ($row = sqlsrv_fetch_array($f_stmt, SQLSRV_FETCH_ASSOC)) {
        $barangays[] = $row['brgy'];
    }
}

// Calculate totals
$total_primary = 0;
$total_secondary = 0;
$total_underbuilt = 0;
$total_transformers = 0;

foreach ($barangays as $brgy) {
    $total_primary += $pole_breakdown[$brgy]['ppole.png'] ?? 0;
    $total_secondary += $pole_breakdown[$brgy]['spole.png'] ?? 0;
    $total_underbuilt += $pole_breakdown[$brgy]['upole.png'] ?? 0;
    $total_transformers += $trn_counts[$brgy] ?? 0;
}
$grand_total = $total_primary + $total_secondary + $total_underbuilt;
?>
<div class="bs-wrap">
  <div class="bs-header">
    <i class="fa fa-map-marker"></i> Barangay Infrastructure Statistics
    <?php if (!empty($vtown)): ?>
      <span class="bs-filter"><i class="fa fa-filter"></i> <?= htmlspecialchars($vtown) ?></span>
    <?php endif; ?>
  </div>

  <?php if (empty($barangays)): ?>
    <div class="bs-empty"><i class="fa fa-search"></i> No barangays found</div>
  <?php else: ?>
    
    <!-- Summary Card -->
    <div class="bs-summary">
      <div class="bs-summary-title">
        <i class="fa fa-pie-chart"></i> Town Summary
      </div>
      <div class="bs-summary-grid">
        <div class="bs-summary-item">
          <div class="bs-summary-label">Total Barangays</div>
          <div class="bs-summary-value" style="color:#007a34;"><?= count($barangays) ?></div>
        </div>
        <div class="bs-summary-item">
          <div class="bs-summary-label">Total Poles</div>
          <div class="bs-summary-value" style="color:#10b981;"><?= $grand_total ?></div>
        </div>
        <div class="bs-summary-item">
          <div class="bs-summary-label">Transformers</div>
          <div class="bs-summary-value" style="color:#d97706;"><?= $total_transformers ?></div>
        </div>
      </div>
      <div class="bs-summary-breakdown">
        <div class="bs-breakdown-item">
          <span class="bs-breakdown-dot" style="background:#007a34;"></span>
          <span class="bs-breakdown-label">Primary: <strong><?= $total_primary ?></strong></span>
        </div>
        <div class="bs-breakdown-item">
          <span class="bs-breakdown-dot" style="background:#3b82f6;"></span>
          <span class="bs-breakdown-label">Secondary: <strong><?= $total_secondary ?></strong></span>
        </div>
        <div class="bs-breakdown-item">
          <span class="bs-breakdown-dot" style="background:#8b5cf6;"></span>
          <span class="bs-breakdown-label">Under Built: <strong><?= $total_underbuilt ?></strong></span>
        </div>
      </div>
    </div>

    <!-- Barangay Cards -->
    <?php foreach ($barangays as $brgy):
      $p   = $pole_breakdown[$brgy]['ppole.png'] ?? 0;
      $s   = $pole_breakdown[$brgy]['spole.png'] ?? 0;
      $u   = $pole_breakdown[$brgy]['upole.png'] ?? 0;
      $tot = $p + $s + $u;
      $trn = $trn_counts[$brgy] ?? 0;
      
      // Calculate percentage of total
      $pct = $grand_total > 0 ? round(($tot / $grand_total) * 100, 1) : 0;
    ?>
    <div class="bs-card brgy-row" data-brgy="<?= htmlspecialchars($brgy) ?>">
      <div class="bs-card-title">
        <div>
          <i class="fa fa-map-marker"></i> <?= htmlspecialchars($brgy) ?>
        </div>
        <span class="bs-percentage"><?= $pct ?>%</span>
      </div>
      <div class="bs-rows">
        <div class="bs-row">
          <span class="bs-label">Primary Poles</span>
          <span class="bs-val primary"><?= $p ?></span>
        </div>
        <div class="bs-row">
          <span class="bs-label">Secondary Poles</span>
          <span class="bs-val secondary"><?= $s ?></span>
        </div>
        <div class="bs-row">
          <span class="bs-label">Under Built Poles</span>
          <span class="bs-val underbuilt"><?= $u ?></span>
        </div>
        <div class="bs-row bs-row-total">
          <span class="bs-label">Total Poles</span>
          <span class="bs-val total"><?= $tot ?></span>
        </div>
        <div class="bs-row">
          <span class="bs-label">Transformers</span>
          <span class="bs-val transformer"><?= $trn ?></span>
        </div>
      </div>
    </div>
    <?php endforeach; ?>
  <?php endif; ?>
</div>

<script>
if (window.parent && window.parent.document) {
  document.querySelectorAll('.brgy-row').forEach(row => {
    row.addEventListener('click', function () {
      const brgy = this.dataset.brgy;
      const vtown = '<?= htmlspecialchars($vtown) ?>';
      
      // Update the barangay selector in parent
      const brgySelect = window.parent.document.getElementById('vbrgy');
      if (brgySelect) {
        // Find and select the matching option
        for (let i = 0; i < brgySelect.options.length; i++) {
          if (brgySelect.options[i].value === brgy) {
            brgySelect.selectedIndex = i;
            break;
          }
        }
      }
      
      // Trigger the barangay customer load
      if (window.parent.loadBrgyCust) {
        window.parent.loadBrgyCust();
      }
    });
  });
}
</script>

<style>
.bs-wrap {
  font-family: 'Segoe UI', sans-serif;
  padding: 4px 2px;
}
.bs-header {
  font-size: 13px;
  font-weight: 700;
  color: #1a1a1a;
  padding: 6px 4px 10px;
  display: flex;
  align-items: center;
  gap: 6px;
  flex-wrap: wrap;
}
.bs-filter {
  font-size: 11px;
  font-weight: 500;
  color: #e67e00;
  background: #fff8ee;
  border: 1px solid #f5c97a;
  border-radius: 20px;
  padding: 2px 8px;
  margin-left: 4px;
}
.bs-empty {
  text-align: center;
  color: #999;
  padding: 24px;
  font-size: 13px;
}

/* Summary Card */
.bs-summary {
  background: linear-gradient(135deg, #012b13 0%, #014f26 100%);
  border-radius: 10px;
  padding: 14px 16px;
  margin-bottom: 12px;
  color: #fff;
  box-shadow: 0 4px 12px rgba(1, 43, 19, 0.2);
}
.bs-summary-title {
  font-size: 12px;
  font-weight: 700;
  letter-spacing: 0.04em;
  margin-bottom: 12px;
  display: flex;
  align-items: center;
  gap: 6px;
  color: rgba(255,255,255,0.85);
}
.bs-summary-grid {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: 10px;
  margin-bottom: 12px;
}
.bs-summary-item {
  background: rgba(255,255,255,0.12);
  border-radius: 8px;
  padding: 10px 8px;
  text-align: center;
  border: 1px solid rgba(255,255,255,0.15);
}
.bs-summary-label {
  font-size: 9px;
  font-weight: 600;
  letter-spacing: 0.06em;
  text-transform: uppercase;
  color: rgba(255,255,255,0.65);
  margin-bottom: 4px;
}
.bs-summary-value {
  font-size: 22px;
  font-weight: 700;
  color: #fff;
  line-height: 1;
}
.bs-summary-breakdown {
  display: flex;
  gap: 12px;
  flex-wrap: wrap;
  padding-top: 10px;
  border-top: 1px solid rgba(255,255,255,0.15);
}
.bs-breakdown-item {
  display: flex;
  align-items: center;
  gap: 5px;
  font-size: 10.5px;
  color: rgba(255,255,255,0.8);
}
.bs-breakdown-dot {
  width: 8px;
  height: 8px;
  border-radius: 50%;
  flex-shrink: 0;
}
.bs-breakdown-label strong {
  font-weight: 700;
  color: #fff;
}

/* Barangay Cards */
.bs-card {
  background: #fff;
  border: 1px solid #e8f0e9;
  border-radius: 8px;
  margin-bottom: 8px;
  overflow: hidden;
  cursor: pointer;
  transition: box-shadow 0.15s, border-color 0.15s, transform 0.15s;
}
.bs-card:hover {
  box-shadow: 0 2px 10px rgba(0,0,0,0.08);
  border-color: #a7d3ae;
  transform: translateX(2px);
}
.bs-card-title {
  background: #012b13;
  color: #fff;
  font-size: 12px;
  font-weight: 700;
  padding: 7px 12px;
  letter-spacing: 0.03em;
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 6px;
}
.bs-card-title > div {
  display: flex;
  align-items: center;
  gap: 6px;
}
.bs-percentage {
  font-size: 10px;
  background: rgba(0, 168, 74, 0.2);
  border: 1px solid rgba(0, 168, 74, 0.35);
  color: #4ade80;
  padding: 2px 8px;
  border-radius: 12px;
  font-weight: 600;
}
.bs-rows {
  padding: 4px 0;
}
.bs-row {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 6px 12px;
  border-bottom: 1px solid #f3f3f3;
  font-size: 12.5px;
}
.bs-row:last-child {
  border-bottom: none;
}
.bs-row-total {
  background: #f6faf6;
}
.bs-label {
  color: #555;
}
.bs-val {
  font-weight: 700;
  font-size: 13px;
  min-width: 36px;
  text-align: right;
}
.bs-val.primary     { color: #007a34; }
.bs-val.secondary   { color: #3b82f6; }
.bs-val.underbuilt  { color: #8b5cf6; }
.bs-val.total       { color: #10b981; }
.bs-val.transformer { color: #d97706; }

/* Mobile responsive */
@media (max-width: 480px) {
  .bs-summary-grid {
    grid-template-columns: 1fr;
  }
  .bs-summary-value {
    font-size: 28px;
  }
}
</style>