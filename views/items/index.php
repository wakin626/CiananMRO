<h1 class="page-title">Dashboard</h1>

<div class="stats-grid">
    <div class="stat-card">
        <span class="stat-value"><?= (int) $stats['total_items'] ?></span>
        <span class="stat-label">Total Consumable Items</span>
    </div>
    <div class="stat-card stat-warning">
        <span class="stat-value"><?= (int) $stats['low_stock'] ?></span>
        <span class="stat-label">Low Stock Warnings</span>
    </div>
    <div class="stat-card stat-warning">
        <span class="stat-value"><?= (int) $stats['out_of_stock'] ?></span>
        <span class="stat-label">Out of Stock Items</span>
    </div>
</div>

<div class="dashboard-grid">
    <div class="card">
        <div class="card-header card-header-flex">
            <span>Recent Requests</span>
            <a href="/CIANANMRO/public/index.php?page=stock_in" class="btn btn-sm btn-primary">See All</a>
        </div>
        <div class="card-body">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Request</th>
                        <th>Date</th>
                        <th>Item</th>
                        <th>Quantity</th>
                        <th>Total Price</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($recentRequests)): ?>
                        <tr><td colspan="6" class="empty">No requests yet.</td></tr>
                    <?php else: ?>
                        <?php foreach ($recentRequests as $req): ?>
                            <?php $badge = ['RECEIVED' => 'badge-ok', 'CANCELLED' => 'badge-low'][$req['status']] ?? 'badge-pending'; ?>
                            <tr>
                                <td>RQ-000<?= (int) $req['id'] ?></td>
                                <td><?= htmlspecialchars(date('Y-m-d', strtotime($req['created_at']))) ?></td>
                                <td><?= htmlspecialchars($req['item_code']) ?></td>
                                <td><?= (int) $req['quantity'] . ' ' . htmlspecialchars($req['item_unit']) ?></td>
                                <td><?= $req['total_price'] !== null ? 'P ' . number_format((float) $req['total_price'], 2) : '&mdash;' ?></td>
                                <td><span class="badge <?= $badge ?>"><?= htmlspecialchars($req['status']) ?></span></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <div class="card">
        <div class="card-header card-header-flex">
            <span>Recent Purchases (Received)</span>
            <a href="/CIANANMRO/public/index.php?page=stock_in" class="btn btn-sm btn-primary">See All</a>
        </div>
        <div class="card-body">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Request</th>
                        <th>Date</th>
                        <th>Item</th>
                        <th>Quantity</th>
                        <th>Total Price</th>
                        <th>Received At</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($recentPurchases)): ?>
                        <tr><td colspan="6" class="empty">No purchases yet.</td></tr>
                    <?php else: ?>
                        <?php foreach ($recentPurchases as $req): ?>
                            <tr>
                                <td>RQ-000<?= (int) $req['id'] ?></td>
                                <td><?= htmlspecialchars(date('Y-m-d', strtotime($req['created_at']))) ?></td>
                                <td><?= htmlspecialchars($req['item_code']) ?></td>
                                <td><?= (int) $req['quantity'] . ' ' . htmlspecialchars($req['item_unit']) ?></td>
                                <td><?= $req['total_price'] !== null ? 'P ' . number_format((float) $req['total_price'], 2) : '&mdash;' ?></td>
                                <td><?= $req['received_at'] ? htmlspecialchars(date('Y-m-d H:i', strtotime($req['received_at']))) : '&mdash;' ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <div class="card card-full">
        <div class="card-header card-header-flex">
            <span>Recent Withdrawals</span>
            <a href="/CIANANMRO/public/index.php?page=stock_out" class="btn btn-sm btn-primary">See All</a>
        </div>
        <div class="card-body">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Withdraw No.</th>
                        <th>Date</th>
                        <th>Item</th>
                        <th>Qty</th>
                        <th>Withdrawer</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($recentWithdrawals)): ?>
                        <tr><td colspan="5" class="empty">No withdrawals yet.</td></tr>
                    <?php else: ?>
                        <?php foreach ($recentWithdrawals as $w): ?>
                            <tr>
                                <td>WD-000<?= (int) $w['id'] ?></td>
                                <td><?= htmlspecialchars(date('Y-m-d H:i', strtotime($w['created_at']))) ?></td>
                                <td><?= htmlspecialchars($w['item_code']) ?></td>
                                <td><?= (int) $w['quantity'] . ' ' . htmlspecialchars($w['item_unit']) ?></td>
                                <td><?= htmlspecialchars($w['withdrawer']) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
