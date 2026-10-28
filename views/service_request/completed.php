<?php $badges = ['CRITICAL' => 'sev-critical', 'HIGH' => 'sev-high', 'MODERATE' => 'sev-moderate', 'LOW' => 'sev-low']; ?>
<?php if (isset($_GET['status']) && $_GET['status'] === 'completed'): ?>
    <div class="alert alert-success">Request marked as completed.</div>
<?php endif; ?>

<h1 class="page-title">Completed Service Requests</h1>

<div class="card">
    <div class="card-header">Completed Requests (latest first)</div>
    <div class="card-body">
        <table class="data-table" id="completedSrTable">
            <thead>
                <tr>
                    <th>SR No.</th>
                    <th>Completed</th>
                    <th>Nature</th>
                    <th>Severity</th>
                    <th>Description</th>
                    <th>Requestor</th>
                    <th>Plant</th>
                    <th>Technician</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($completed)): ?>
                    <tr><td colspan="8" class="empty">No completed requests yet.</td></tr>
                <?php else: ?>
                    <?php foreach ($completed as $sr): ?>
                        <tr>
                            <td>SR-<?= str_pad((string) $sr['id'], 4, '0', STR_PAD_LEFT) ?></td>
                            <td><?= htmlspecialchars(date('Y-m-d H:i', strtotime($sr['completed_at']))) ?></td>
                            <td><?= htmlspecialchars($sr['nature_of_request'] ?: '') ?></td>
                            <td>
                                <?php if ($sr['severity']): ?>
                                    <span class="kanban-sev <?= $badges[$sr['severity']] ?>"><?= htmlspecialchars($sr['severity']) ?></span>
                                <?php endif; ?>
                            </td>
                            <td><?= nl2br(htmlspecialchars($sr['description'])) ?></td>
                            <td><?= htmlspecialchars($sr['requested_by']) ?><br><small><?= htmlspecialchars($sr['department'] ?: '') ?></small></td>
                            <td><?= htmlspecialchars($sr['plant'] ?: '') ?></td>
                            <td>
                                <?php foreach ($assignMap[(int) $sr['id']] ?? [] as $techLabel) {
                                    echo $techLabel . '<br>';
                                    } ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php if (isset($pagination) && $pagination['totalPages'] > 1): ?>
<?php
    $pg = $pagination['pg'];
    $perPage = $pagination['perPage'];
    $totalPages = $pagination['totalPages'];
    $baseUrl = '/CIANANMRO/public/index.php?page=completed_sr&per_page=' . $perPage;
    $start = max(1, $pg - 2);
    $end = min($totalPages, $pg + 2);
    $from = $pagination['total'] > 0 ? ($pg - 1) * $perPage + 1 : 0;
    $to = min($pg * $perPage, $pagination['total']);
?>
<div class="table-footer">
    <span class="page-info">
        Showing <?= $from ?>&ndash;<?= $to ?> of <?= $pagination['total'] ?> completed requests
    </span>

    <nav class="pagination" aria-label="Pagination">
        <a class="page-link <?= $pg <= 1 ? 'disabled' : '' ?>"
           href="<?= $baseUrl . '&pg=' . max(1, $pg - 1) ?>">&laquo;</a>

        <?php if ($start > 1): ?>
            <a class="page-link" href="<?= $baseUrl . '&pg=1' ?>">1</a>
            <?php if ($start > 2): ?><span class="page-ellipsis">&hellip;</span><?php endif; ?>
        <?php endif; ?>

        <?php for ($i = $start; $i <= $end; $i++): ?>
            <a class="page-link <?= $i === $pg ? 'active' : '' ?>" href="<?= $baseUrl . '&pg=' . $i ?>"><?= $i ?></a>
        <?php endfor; ?>

        <?php if ($end < $totalPages): ?>
            <?php if ($end < $totalPages - 1): ?><span class="page-ellipsis">&hellip;</span><?php endif; ?>
            <a class="page-link" href="<?= $baseUrl . '&pg=' . $totalPages ?>"><?= $totalPages ?></a>
        <?php endif; ?>

        <a class="page-link <?= $pg >= $totalPages ? 'disabled' : '' ?>"
           href="<?= $baseUrl . '&pg=' . min($totalPages, $pg + 1) ?>">&laquo;</a>
    </nav>
</div>
<?php endif; ?>
