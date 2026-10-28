<?php $exportUrl = '/CIANANMRO/public/index.php?page=export_report_csv&report=' . urlencode($type)
    . ($filters['date_from'] ? '&date_from=' . $filters['date_from'] : '')
    . ($filters['date_to'] ? '&date_to=' . $filters['date_to'] : '');
?>

<h1 class="page-title">Reports</h1>

<div class="card report-filterbar">
    <form method="get" action="/CIANANMRO/public/index.php" class="report-filter-form">
        <input type="hidden" name="page" value="reports">
        <div class="filter-field">
            <label for="date_from">From Date</label>
            <input type="date" id="date_from" name="date_from" value="<?= htmlspecialchars($filters['date_from']) ?>" <?= $dateSensitive ? '' : 'disabled' ?>>
        </div>
        <div class="filter-field">
            <label for="date_to">To Date</label>
            <input type="date" id="date_to" name="date_to" value="<?= htmlspecialchars($filters['date_to']) ?>" <?= $dateSensitive ? '' : 'disabled' ?>>
        </div>
        <div class="filter-field filter-field-wide">
            <label for="report">Report Type</label>
            <select id="report" name="report">
                <option value="inventory" <?= $type === 'inventory' ? 'selected' : '' ?>>Inventory Valuation &amp; Stock Status Summary</option>
                <option value="stock_in" <?= $type === 'stock_in' ? 'selected' : '' ?>>Stock-In Analytics</option>
                <option value="stock_out" <?= $type === 'stock_out' ? 'selected' : '' ?>>Stock-Out / Machine Usage Analytics</option>
            </select>
        </div>
        <button type="submit" class="btn btn-primary">Apply Filters</button>
        <a href="<?= htmlspecialchars($exportUrl) ?>" class="btn btn-secondary no-print">Export to CSV</a>
        <button type="button" class="btn btn-secondary no-print" onclick="window.print()">Print Report</button>
    </form>
</div>

<div class="stats-grid">
    <div class="stat-card">
        <span class="stat-value"><?= number_format($kpis['total_items']) ?></span>
        <span class="stat-label">Total Items Managed</span>
    </div>
    <div class="stat-card stat-warning">
        <span class="stat-value"><?= number_format($kpis['out_of_stock']) ?></span>
        <span class="stat-label">Out of Stock Items</span>
    </div>
    <?php if ($type === 'stock_in'): ?>
        <div class="stat-card">
            <span class="stat-value"><?= number_format($kpis['stock_in_qty']) ?></span>
            <span class="stat-label">Total Stock-In Volume</span>
        </div>
    <?php elseif ($type === 'stock_out'): ?>
        <div class="stat-card">
            <span class="stat-value"><?= number_format($kpis['stock_out_qty']) ?></span>
            <span class="stat-label">Total Stock-Out Volume</span>
        </div>
    <?php else: ?>
        <div class="stat-card">
            <span class="stat-value"><?= number_format($kpis['stock_in_qty']) ?></span>
            <span class="stat-label">Total Stock-In Volume</span>
        </div>
        <div class="stat-card">
            <span class="stat-value"><?= number_format($kpis['stock_out_qty']) ?></span>
            <span class="stat-label">Total Stock-Out Volume</span>
        </div>
    <?php endif; ?>
</div>

<div class="card trend-card">
    <div class="card-header card-header-flex">
        <span>Stock-In &amp; Stock-Out Trends</span>
        <form method="get" action="/CIANANMRO/public/index.php" class="no-print">
            <input type="hidden" name="page" value="reports">
            <input type="hidden" name="report" value="<?= htmlspecialchars($type) ?>">
            <input type="hidden" name="date_from" value="<?= htmlspecialchars($filters['date_from']) ?>">
            <input type="hidden" name="date_to" value="<?= htmlspecialchars($filters['date_to']) ?>">
            <select id="trendInterval" name="interval" title="Chart granularity">
                <option value="monthly" <?= $interval === 'monthly' ? 'selected' : '' ?>>Monthly</option>
                <option value="weekly" <?= $interval === 'weekly' ? 'selected' : '' ?>>Weekly</option>
                <option value="daily" <?= $interval === 'daily' ? 'selected' : '' ?>>Daily</option>
            </select>
        </form>
    </div>
    <div class="card-body trend-body">
        <canvas id="trendChart" aria-label="Stock-In and Stock-Out trend chart"></canvas>
        <?php if (empty($trendLabels)): ?>
            <div class="empty" style="padding: 1.5rem">No transactions in this period.</div>
        <?php endif; ?>
    </div>
</div>

<?php if (!empty($sections)): ?>
<?php foreach ($sections as $section): ?>
<div class="card">
    <div class="card-header"><?= htmlspecialchars($section['title']) ?></div>
    <div class="card-body">
        <table class="data-table">
            <thead>
                <tr>
                    <?php foreach ($section['headers'] as $h): ?>
                        <th><?= $h ?></th>
                    <?php endforeach; ?>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($section['rows'])): ?>
                    <tr><td colspan="<?= count($section['headers']) ?>" class="empty">No data for this period.</td></tr>
                <?php else: ?>
                    <?php foreach ($section['rows'] as $row): ?>
                        <tr>
                            <?php foreach ($row as $i => $value): ?>
                                <?php $header = $section['headers'][$i] ?? ''; ?>
                                <td>
                                    <?php if ($header === 'Status'): ?>
                                        <span class="badge <?= ['IN STOCK' => 'badge-ok', 'RECEIVED' => 'badge-ok', 'LOW STOCK' => 'badge-warning', 'PENDING' => 'badge-pending', 'CANCELLED' => 'badge-low', 'OUT OF STOCK' => 'badge-out'][$value] ?? 'badge-ok' ?>"><?= htmlspecialchars((string) $value) ?></span>
                                    <?php elseif (str_ends_with($header, '(P)')): ?>
                                        <?= $value !== null && $value !== '' ? 'P ' . number_format((float) $value, 2) : '&mdash;' ?>
                                    <?php elseif (str_contains($header, 'Date') || str_contains($header, 'Last')): ?>
                                        <?= $value !== null && $value !== '' ? htmlspecialchars(date('Y-m-d H:i', strtotime($value))) : '&mdash;' ?>
                                    <?php else: ?>
                                        <?= htmlspecialchars((string) ($value ?? '')) ?>
                                    <?php endif; ?>
                                </td>
                            <?php endforeach; ?>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
<?php endforeach; ?>
<?php endif; ?>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    var intervalSelect = document.getElementById('trendInterval');
    intervalSelect.addEventListener('change', function () {
        intervalSelect.form.submit();
    });

    var reportSelect = document.getElementById('report');
    reportSelect.addEventListener('change', function () {
        reportSelect.closest('form').submit();
    });

    var ctx = document.getElementById('trendChart');
    if (!ctx || !window.Chart || <?= count($trendLabels) ?> === 0) return;

    new Chart(ctx.getContext('2d'), {
        type: 'bar',
        data: {
            labels: <?= json_encode($trendLabels) ?>,
            datasets: [
                {
                    label: 'Stock-In Transactions',
                    data: <?= json_encode(array_values($trendIn)) ?>,
                    backgroundColor: '#5C7057',
                    borderColor: '#5C7057',
                    borderWidth: 2,
                    yAxisID: 'yTxn',
                    type: 'line',
                    tension: 0.3
                },
                {
                    label: 'Stock-In Volume',
                    data: <?= json_encode(array_values($trendInQty)) ?>,
                    backgroundColor: 'rgba(92, 112, 87, 0.55)',
                    yAxisID: 'yQty'
                },
                {
                    label: 'Stock-Out Transactions',
                    data: <?= json_encode(array_values($trendOut)) ?>,
                    backgroundColor: '#89A482',
                    borderColor: '#89A482',
                    borderWidth: 2,
                    yAxisID: 'yTxn',
                    type: 'line',
                    tension: 0.3
                },
                {
                    label: 'Stock-Out Volume',
                    data: <?= json_encode(array_values($trendOutQty)) ?>,
                    backgroundColor: 'rgba(137, 164, 130, 0.55)',
                    yAxisID: 'yQty'
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            interaction: { intersect: false, mode: 'index' },
            scales: {
                yTxn: {
                    position: 'left',
                    title: { display: true, text: 'Transactions' },
                    beginAtZero: true,
                    ticks: { precision: 0 },
                    grid: { drawOnChartArea: false }
                },
                yQty: {
                    position: 'right',
                    title: { display: true, text: 'Quantity' },
                    beginAtZero: true,
                    ticks: { precision: 0 }
                }
            },
            plugins: {
                legend: { position: 'bottom' }
            }
        }
    });
});
</script>

<style>
.report-filterbar { margin-bottom: 1.5rem; }
.report-filter-form {
    display: flex;
    flex-wrap: wrap;
    gap: 1rem;
    align-items: flex-end;
    padding: 1rem 1.25rem;
}
.filter-field { display: flex; flex-direction: column; gap: 0.3rem; min-width: 10rem; }
.filter-field-wide { flex: 1; min-width: 16rem; }
.filter-field label {
    font-weight: 600;
    font-size: 0.82rem;
    color: #334155;
}
.filter-field input,
.filter-field select {
    padding: 0.5rem;
    border: 1px solid var(--border-soft);
    border-radius: 4px;
    font-size: 0.95rem;
    background: #fff;
    width: 100%;
}
.filter-field input:disabled { background: var(--sage-tint); color: #94a3b8; }
.report-filter-form .btn { white-space: nowrap; }
.trend-card .trend-body {
    padding: 1rem;
    height: 320px;
    display: flex;
    align-items: stretch;
    flex-direction: column;
}
.trend-card canvas { flex: 1; min-width: 0; }
.trend-card .card-header select {
    padding: 0.35rem 0.5rem;
    border: 1px solid rgba(0, 0, 0, 0.25);
    border-radius: 4px;
    font-size: 0.85rem;
    background: #fff;
    color: #2f3029;
    cursor: pointer;
}
@media print {
    .sidebar, .sidebar-reopen, .no-print, .report-filter-form .btn {
        display: none !important;
    }
    .main-content { padding: 0; }
}
</style>
