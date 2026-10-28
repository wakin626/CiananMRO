<?php $badges = ['IN STOCK' => 'badge-ok', 'LOW STOCK' => 'badge-warning', 'OUT OF STOCK' => 'badge-out']; ?>

<?php if (isset($error)): ?>
    <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
<?php endif; ?>

<?php if (isset($_GET['status']) && $_GET['status'] === 'added'): ?>
    <div class="alert alert-success">Consumable item added successfully.</div>
<?php elseif (isset($_GET['status']) && $_GET['status'] === 'updated'): ?>
    <div class="alert alert-success">Consumable item updated successfully.</div>
<?php elseif (isset($_GET['status']) && $_GET['status'] === 'uploaded'): ?>
    <div class="alert alert-success"><?= htmlspecialchars(urldecode($_GET['msg'] ?? 'Items uploaded.')) ?></div>
<?php elseif (isset($_GET['status']) && $_GET['status'] === 'deleted'): ?>
    <div class="alert alert-success">Consumable item deleted successfully.</div>
<?php endif; ?>

<h1 class="page-title">Maintenance Consumables</h1>

<div class="toolbar">
    <button type="button" class="btn btn-primary" id="openAddModal">+ Add Consumable Item</button>
    <button type="button" class="btn btn-secondary" id="openUploadModal">Bulk Upload CSV</button>
</div>

<div class="filter-toolbar">
    <input type="search" id="consumableSearch" placeholder="Live search by item code, description..." value="<?= htmlspecialchars($filters['search']) ?>" autocomplete="off">
    <div class="filter-inline">
        <label for="supplierFilter">Supplier</label>
        <select id="supplierFilter">
            <option value="">All Suppliers</option>
            <?php foreach ($suppliers as $sup): ?>
                <option value="<?= htmlspecialchars($sup) ?>"<?= $filters['supplier'] === $sup ? ' selected' : '' ?>><?= htmlspecialchars($sup) ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="filter-inline">
        <label for="statusFilter">Status</label>
        <select id="statusFilter">
            <option value="">All Statuses</option>
            <option value="IN STOCK" <?= $filters['status'] === 'IN STOCK' ? 'selected' : '' ?>>IN STOCK</option>
            <option value="LOW STOCK" <?= $filters['status'] === 'LOW STOCK' ? 'selected' : '' ?>>LOW STOCK</option>
            <option value="OUT OF STOCK" <?= $filters['status'] === 'OUT OF STOCK' ? 'selected' : '' ?>>OUT OF STOCK</option>
        </select>
    </div>
</div>

<div id="tableArea">
<div class="card">
    <div class="card-header">Consumables List</div>
    <div class="card-body">
        <table class="data-table" id="consumablesTable">
            <thead>
                <tr>
                    <th>Item Code</th>
                    <th>Description</th>
                    <th>Supplier</th>
                    <th>Actual Qty</th>
                    <th>Req Qty</th>
                    <th>UOM</th>
                    <th>Status</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($consumables)): ?>
                    <tr><td colspan="8" class="empty">No consumable items found.</td></tr>
                <?php else: ?>
                    <?php foreach ($consumables as $row): ?>
                        <tr data-details="<?= htmlspecialchars(json_encode([
                            'id'                => (int) $row['id'],
                            'item_code'         => $row['item_code'],
                            'item_name'         => $row['item_name'],
                            'description'       => $row['description'] ?? '',
                            'supplier'          => $row['supplier'] ?? '',
                            'machine_equipment' => $row['machine_equipment'] ?? '',
                            'identification'    => $row['identification'] ?? '',
                            'recommended_qty'   => (int) $row['recommended_qty'],
                            'low_level_qty'     => (int) $row['low_level_qty'],
                            'actual_stock_qty'  => (int) $row['actual_stock_qty'],
                            'request_qty'       => (int) $row['request_qty'],
                            'uom'               => $row['uom'],
                            'price_per_piece'   => $row['price_per_piece'],
                        ]), ENT_QUOTES, 'UTF-8') ?>">
                            <td><?= htmlspecialchars($row['item_code']) ?></td>
                            <td><?= htmlspecialchars($row['description'] ?: $row['item_name']) ?></td>
                            <td><?= htmlspecialchars($row['supplier'] ?? '') ?></td>
                            <td><?= (int) $row['actual_stock_qty'] ?></td>
                            <td><?= (int) $row['request_qty'] ?></td>
                            <td><?= htmlspecialchars($row['uom']) ?></td>
                            <td>
                                <span class="badge <?= $badges[$row['status']] ?? 'badge-ok' ?>">
                                    <?= htmlspecialchars($row['status']) ?>
                                </span>
                            </td>
                            <td>
                                <div class="action-icons">
                                    <button type="button" class="btn-icon btn-view view-details-btn" title="View Details" aria-label="View Details">
                                        <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                                    </button>
                                    <button type="button" class="btn-icon btn-edit edit-btn" title="Edit" aria-label="Edit">
                                        <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17 3a2.828 2.828 0 1 1 4 4L7.5 20.5 2 22l1.5-5.5L17 3z"/></svg>
                                    </button>
                                    <form method="post" action="/CIANANMRO/public/index.php?page=delete_consumable" class="delete-form" onsubmit="return confirm('Delete this consumable item? This cannot be undone.');">
                                        <input type="hidden" name="id" value="<?= (int) $row['id'] ?>">
                                        <button type="submit" class="btn-icon btn-delete" title="Delete" aria-label="Delete">
                                            <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="3 6 5 6 21 6"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/><line x1="10" y1="11" x2="10" y2="17"/><line x1="14" y1="11" x2="14" y2="17"/></svg>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php if (isset($pagination) && $pagination['totalPages'] > 0): ?>
<?php
    $pg = $pagination['pg'];
    $perPage = $pagination['perPage'];
    $totalPages = $pagination['totalPages'];
    $filterQuery = http_build_query(array_filter([
        'q'             => $filters['search'],
        'supplier'      => $filters['supplier'],
        'status_filter' => $filters['status'],
    ], 'strlen'));
    $baseUrl = '/CIANANMRO/public/index.php?page=consumables&per_page=' . $perPage . ($filterQuery !== '' ? '&' . $filterQuery : '');
    $start = max(1, $pg - 2);
    $end = min($totalPages, $pg + 2);
    $from = $pagination['total'] > 0 ? ($pg - 1) * $perPage + 1 : 0;
    $to = min($pg * $perPage, $pagination['total']);
?>
<div class="table-footer">
    <span class="page-info">
        Showing <?= $from ?>&ndash;<?= $to ?> of <?= $pagination['total'] ?> items
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
           href="<?= $baseUrl . '&pg=' . min($totalPages, $pg + 1) ?>">&raquo;</a>
    </nav>

    <form method="get" action="/CIANANMRO/public/index.php" class="per-page">
        <input type="hidden" name="page" value="consumables">
        <input type="hidden" name="pg" value="1">
        <?php if ($filters['search'] !== ''): ?>
            <input type="hidden" name="q" value="<?= htmlspecialchars($filters['search']) ?>">
        <?php endif; ?>
        <?php if ($filters['supplier'] !== ''): ?>
            <input type="hidden" name="supplier" value="<?= htmlspecialchars($filters['supplier']) ?>">
        <?php endif; ?>
        <?php if ($filters['status'] !== ''): ?>
            <input type="hidden" name="status_filter" value="<?= htmlspecialchars($filters['status']) ?>">
        <?php endif; ?>
        <label for="per_page">Rows per page</label>
        <select name="per_page" id="per_page" onchange="this.form.submit()">
            <option value="10" <?= $perPage === 10 ? 'selected' : '' ?>>10</option>
            <option value="25" <?= $perPage === 25 ? 'selected' : '' ?>>25</option>
            <option value="50" <?= $perPage === 50 ? 'selected' : '' ?>>50</option>
            <option value="100" <?= $perPage === 100 ? 'selected' : '' ?>>100</option>
        </select>
    </form>
</div>
<?php endif; ?>
</div>

<!-- Add Consumable Modal -->
<div class="modal-overlay" id="addModal">
    <div class="modal">
        <div class="modal-header">
            <h2>Add Consumable Item</h2>
            <button type="button" class="modal-close" data-close-modal="addModal">&times;</button>
        </div>
        <form method="post" action="/CIANANMRO/public/index.php?page=submit_consumable" class="modal-form">
            <div class="form-row">
                <div class="form-field">
                    <label for="item_code">Item Code</label>
                    <input type="text" name="item_code" id="item_code">
                </div>
            </div>

            <div class="form-field">
                <label for="description">Description</label>
                <textarea name="description" id="description" rows="2"></textarea>
            </div>

            <div class="form-row">
                <div class="form-field">
                    <label for="supplier">Supplier</label>
                    <input type="text" name="supplier" id="supplier">
                </div>
                <div class="form-field">
                    <label for="machine_equipment">Machine / Equipment</label>
                    <input type="text" name="machine_equipment" id="machine_equipment">
                </div>
            </div>

            <div class="form-field">
                <label for="identification">Identification</label>
                <input type="text" name="identification" id="identification">
            </div>

            <div class="form-row">
                <div class="form-field">
                    <label for="recommended_qty">Recommended Qty</label>
                    <input type="number" name="recommended_qty" id="recommended_qty" min="0" value="0">
                </div>
                <div class="form-field">
                    <label for="low_level_qty">Low Level Qty</label>
                    <input type="number" name="low_level_qty" id="low_level_qty" min="0" value="0">
                </div>
            </div>

            <div class="form-row">
                <div class="form-field">
                    <label for="actual_stock_qty">Actual Stock Qty</label>
                    <input type="number" name="actual_stock_qty" id="actual_stock_qty" min="0" value="0">
                </div>
                <div class="form-field">
                    <label for="request_qty">Request Qty</label>
                    <input type="number" name="request_qty" id="request_qty" min="0" value="0">
                </div>
            </div>

            <div class="form-row">
                <div class="form-field">
                    <label for="uom">UOM</label>
                    <input type="text" name="uom" id="uom" value="pcs">
                </div>
                <div class="form-field">
                    <label for="price_per_piece">Price Per Piece</label>
                    <input type="number" name="price_per_piece" id="price_per_piece" min="0" step="0.01" value="0.00">
                </div>
            </div>

            <div class="modal-actions">
                <button type="button" class="btn btn-secondary" data-close-modal="addModal">Cancel</button>
                <button type="submit" class="btn btn-primary">Save Consumable</button>
            </div>
        </form>
    </div>
</div>

<!-- Bulk Upload Modal -->
<div class="modal-overlay" id="uploadModal">
    <div class="modal">
        <div class="modal-header">
            <h2>Bulk Upload Consumables</h2>
            <button type="button" class="modal-close" data-close-modal="uploadModal">&times;</button>
        </div>
        <form method="post" action="/CIANANMRO/public/index.php?page=upload_consumables" enctype="multipart/form-data" class="modal-form">
            <p class="upload-note">
                Upload a CSV file with the columns:
                <strong>ITEM CODE, DESCRIPTION, SUPPLIER, UOM, IDENTIFICATION, RECOMMENDED QTY, LOW LEVEL</strong>
                (qty columns are optional). The DESCRIPTION column is used as the Item Name &mdash;
                multi-line descriptions are combined into one name. In Excel, use
                <strong>File &gt; Save As &gt; CSV</strong>.
                Merged cells (two-row items with a merged Item Code) are handled automatically &mdash;
                the second row is merged into the item above it. Rows with an existing Item Code are skipped.
            </p>
            <div class="form-field">
                <label for="csv_file">CSV File</label>
                <input type="file" name="csv_file" id="csv_file" accept=".csv" required>
            </div>
            <div class="modal-actions">
                <button type="button" class="btn btn-secondary" data-close-modal="uploadModal">Cancel</button>
                <button type="submit" class="btn btn-primary">Upload</button>
            </div>
        </form>
    </div>
</div>

<!-- Edit Consumable Modal -->
<div class="modal-overlay" id="editModal">
    <div class="modal">
        <div class="modal-header">
            <h2>Edit Consumable Item</h2>
            <button type="button" class="modal-close" data-close-modal="editModal">&times;</button>
        </div>
        <form method="post" action="/CIANANMRO/public/index.php?page=update_consumable" class="modal-form">
            <input type="hidden" name="id" id="edit_id">

            <div class="form-row">
                <div class="form-field">
                    <label for="edit_item_code">Item Code</label>
                    <input type="text" name="item_code" id="edit_item_code">
                </div>
            </div>

            <div class="form-field">
                <label for="edit_description">Description</label>
                <textarea name="description" id="edit_description" rows="2"></textarea>
            </div>

            <div class="form-row">
                <div class="form-field">
                    <label for="edit_supplier">Supplier</label>
                    <input type="text" name="supplier" id="edit_supplier">
                </div>
                <div class="form-field">
                    <label for="edit_machine_equipment">Machine / Equipment</label>
                    <input type="text" name="machine_equipment" id="edit_machine_equipment">
                </div>
            </div>

            <div class="form-field">
                <label for="edit_identification">Identification</label>
                <input type="text" name="identification" id="edit_identification">
            </div>

            <div class="form-row">
                <div class="form-field">
                    <label for="edit_recommended_qty">Recommended Qty</label>
                    <input type="number" name="recommended_qty" id="edit_recommended_qty" min="0" value="0">
                </div>
                <div class="form-field">
                    <label for="edit_low_level_qty">Low Level Qty</label>
                    <input type="number" name="low_level_qty" id="edit_low_level_qty" min="0" value="0">
                </div>
            </div>

            <div class="form-row">
                <div class="form-field">
                    <label for="edit_actual_stock_qty">Actual Stock Qty</label>
                    <input type="number" name="actual_stock_qty" id="edit_actual_stock_qty" min="0" value="0">
                </div>
                <div class="form-field">
                    <label for="edit_request_qty">Request Qty</label>
                    <input type="number" name="request_qty" id="edit_request_qty" min="0" value="0">
                </div>
            </div>

            <div class="form-row">
                <div class="form-field">
                    <label for="edit_uom">UOM</label>
                    <input type="text" name="uom" id="edit_uom">
                </div>
                <div class="form-field">
                    <label for="edit_price_per_piece">Price Per Piece</label>
                    <input type="number" name="price_per_piece" id="edit_price_per_piece" min="0" step="0.01" value="0.00">
                </div>
            </div>

            <div class="modal-actions">
                <button type="button" class="btn btn-secondary" data-close-modal="editModal">Cancel</button>
                <button type="submit" class="btn btn-primary">Save Changes</button>
            </div>
        </form>
    </div>
</div>

<!-- View Details Modal -->
<div class="modal-overlay" id="detailsModal">
    <div class="modal">
        <div class="modal-header">
            <h2>Item Details</h2>
            <button type="button" class="modal-close" data-close-modal="detailsModal">&times;</button>
        </div>
        <div class="modal-form">
            <div class="details-title" id="detailItemName"></div>
            <dl class="details-list">
                <dt>Item Code</dt><dd id="detailItemCode"></dd>
                <dt>Machine / Equipment</dt><dd id="detailMachine"></dd>
                <dt>Supplier</dt><dd id="detailSupplier"></dd>
                <dt>Recommended Qty</dt><dd id="detailRecommended"></dd>
                <dt>Low Level Qty</dt><dd id="detailLowLevel"></dd>
                <dt>Actual Stock Qty</dt><dd id="detailActual"></dd>
                <dt>Request Qty</dt><dd id="detailRequest"></dd>
                <dt>UOM</dt><dd id="detailUom"></dd>
                <dt>Identification</dt><dd id="detailIdentification"></dd>
                <dt>Price Per Piece</dt><dd id="detailPrice"></dd>
            </dl>
            <div class="modal-actions">
                <button type="button" class="btn btn-secondary" data-close-modal="detailsModal">Close</button>
            </div>
        </div>
    </div>
</div>

<script>
(function () {
    function openModal(id) {
        document.getElementById(id).classList.add('open');
    }

    function closeModal(el) {
        el.classList.remove('open');
    }

    document.querySelectorAll('[data-close-modal]').forEach(function (btn) {
        btn.addEventListener('click', function () {
            closeModal(document.getElementById(btn.getAttribute('data-close-modal')));
        });
    });

    document.querySelectorAll('.modal-overlay').forEach(function (overlay) {
        overlay.addEventListener('click', function (e) {
            if (e.target === overlay) {
                closeModal(overlay);
            }
        });
    });

    document.getElementById('openAddModal').addEventListener('click', function () {
        openModal('addModal');
    });

    document.getElementById('openUploadModal').addEventListener('click', function () {
        openModal('uploadModal');
    });

    function applyDetails(d) {
        document.getElementById('detailItemName').textContent = d.item_name;
        document.getElementById('detailItemCode').textContent = d.item_code;
        document.getElementById('detailMachine').textContent = d.machine_equipment || '\u2014';
        document.getElementById('detailSupplier').textContent = d.supplier || '\u2014';
        document.getElementById('detailRecommended').textContent = d.recommended_qty;
        document.getElementById('detailLowLevel').textContent = d.low_level_qty;
        document.getElementById('detailActual').textContent = d.actual_stock_qty;
        document.getElementById('detailRequest').textContent = d.request_qty;
        document.getElementById('detailUom').textContent = d.uom;
        document.getElementById('detailIdentification').textContent = d.identification || '\u2014';
        document.getElementById('detailPrice').textContent = d.price_per_piece;
        openModal('detailsModal');
    }

    function bindRowActions() {
        document.querySelectorAll('.edit-btn').forEach(function (btn) {
            if (btn.dataset.bound) return;
            btn.dataset.bound = '1';
            btn.addEventListener('click', function () {
                var d = JSON.parse(btn.closest('tr').getAttribute('data-details'));

                document.getElementById('edit_id').value = d.id;
                document.getElementById('edit_item_code').value = d.item_code;
                document.getElementById('edit_description').value = d.item_name;
                document.getElementById('edit_supplier').value = d.supplier;
                document.getElementById('edit_machine_equipment').value = d.machine_equipment;
                document.getElementById('edit_identification').value = d.identification;
                document.getElementById('edit_recommended_qty').value = d.recommended_qty;
                document.getElementById('edit_low_level_qty').value = d.low_level_qty;
                document.getElementById('edit_actual_stock_qty').value = d.actual_stock_qty;
                document.getElementById('edit_request_qty').value = d.request_qty;
                document.getElementById('edit_uom').value = d.uom;
                document.getElementById('edit_price_per_piece').value = d.price_per_piece;

                openModal('editModal');
            });
        });

        document.querySelectorAll('.view-details-btn').forEach(function (btn) {
            if (btn.dataset.bound) return;
            btn.dataset.bound = '1';
            btn.addEventListener('click', function () {
                applyDetails(JSON.parse(btn.closest('tr').getAttribute('data-details')));
            });
        });
    }

    bindRowActions();

    var searchInput = document.getElementById('consumableSearch');
    var supplierFilter = document.getElementById('supplierFilter');
    var statusFilter = document.getElementById('statusFilter');
    var tableArea = document.getElementById('tableArea');
    var searchTimer = null;
    var lastSearch = searchInput.value;

    function fetchFiltered() {
        var params = new URLSearchParams(window.location.search);
        params.set('page', 'consumables');
        params.set('pg', '1');

        var q = searchInput.value;
        if (q !== '') { params.set('q', q); } else { params.delete('q'); }
        if (supplierFilter.value !== '') { params.set('supplier', supplierFilter.value); } else { params.delete('supplier'); }
        if (statusFilter.value !== '') { params.set('status_filter', statusFilter.value); } else { params.delete('status_filter'); }

        var url = '/CIANANMRO/public/index.php?' + params.toString();
        lastSearch = q;

        fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
            .then(function (res) { return res.text(); })
            .then(function (html) {
                var doc = new DOMParser().parseFromString(html, 'text/html');
                var fresh = doc.getElementById('tableArea');
                if (!fresh) return;
                tableArea.innerHTML = fresh.innerHTML;
                bindRowActions();
                window.history.replaceState(null, '', url);
            })
            .catch(function () {
                window.location.href = url;
            });
    }

    searchInput.addEventListener('input', function () {
        clearTimeout(searchTimer);
        searchTimer = setTimeout(function () {
            if (searchInput.value === lastSearch) return;
            fetchFiltered();
        }, 250);
    });

    supplierFilter.addEventListener('change', function () { fetchFiltered(); });
    statusFilter.addEventListener('change', function () { fetchFiltered(); });
})();
</script>
