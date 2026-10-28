<?php
$flashStatus = $_GET['status'] ?? '';
$flashMap = [
    'request_success'          => ['Request logged successfully.', 'success'],
    'request_received_success' => ['Request received &mdash; stock on hand updated.', 'success'],
    'request_cancel_success'   => ['Request cancelled.', 'success'],
    'request_forbidden'        => ['Access denied &mdash; only admins can receive or cancel requests.', 'danger'],
    'request_error'            => ['Something went wrong. Please try again.', 'danger'],
];
if (isset($flashMap[$flashStatus])):
    [$flashText, $flashType] = $flashMap[$flashStatus]; ?>
    <div class="alert alert-<?= $flashType ?>"><?= $flashText ?></div>
<?php elseif (isset($error)): ?>
    <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
<?php endif; ?>

<h1 class="page-title">Stock In &mdash; Request</h1>

<div class="filter-toolbar">
    <input type="search" id="inSearch" placeholder="Search by request no., item, code, or notes...">
    <div class="filter-inline">
        <label for="inFrom">From</label>
        <input type="date" id="inFrom">
    </div>
    <div class="filter-inline">
        <label for="inTo">To</label>
        <input type="date" id="inTo">
    </div>
    <select id="inStatus">
        <option value="">All Statuses</option>
        <option value="PENDING">PENDING</option>
        <option value="RECEIVED">RECEIVED</option>
        <option value="CANCELLED">CANCELLED</option>
    </select>
    <select id="inItem">
        <option value="">All Items</option>
        <?php
        $itemNames = [];
        foreach ($requestItems as $it) {
            $itemNames[$it['item_name'] . '|' . $it['item_code']] = $it['item_name'];
        }
        ksort($itemNames);
        foreach ($itemNames as $key => $name): ?>
            <option value="<?= htmlspecialchars($key) ?>"><?= htmlspecialchars($name) ?></option>
        <?php endforeach; ?>
    </select>
</div>

<div class="card">
    <div class="card-header card-header-flex">
        <span>Requests</span>
        <button type="button" class="btn btn-primary" id="openAddRequestModal">+ Add Request</button>
    </div>
    <div class="card-body">
        <table class="data-table" id="requestsTable">
            <thead>
                <tr>
                    <th data-sort="0" data-type="num">Request</th>
                    <th data-sort="1" data-type="date">Date</th>
                    <th>Item</th>
                    <th>Specification</th>
                    <th data-sort="4" data-type="num">Quantity</th>
                    <th>Notes</th>
                    <th data-sort="6" data-type="num">Total Price</th>
                    <th data-sort="7">Status</th>
                    <th data-sort="8" data-type="date">Date Received</th>
                    <th data-sort="9">Requestor</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($requestHistory)): ?>
                    <tr><td colspan="11" class="empty">No requests yet.</td></tr>
                <?php else: ?>
                    <?php foreach ($requestHistory as $req): ?>
                        <?php
                        $isPending = $req['status'] === 'PENDING';
                        $badge = ['RECEIVED' => 'badge-ok', 'CANCELLED' => 'badge-low'][$req['status']] ?? 'badge-pending';
                        ?>
                        <tr>
                            <td>RQ-000<?= (int) $req['id'] ?></td>
                            <td><?= htmlspecialchars(date('Y-m-d', strtotime($req['created_at']))) ?></td>
                            <td><?= htmlspecialchars($req['item_code']) ?></td>
                            <td><?= htmlspecialchars($req['item_description'] ?: $req['item_name']) ?></td>
                            <td><?= (int) $req['quantity'] . ' ' . htmlspecialchars($req['item_unit']) ?></td>
                            <td><?= $req['notes'] !== null && $req['notes'] !== '' ? htmlspecialchars($req['notes']) : '&mdash;' ?></td>
                            <td><?= $req['total_price'] !== null ? 'P ' . number_format((float) $req['total_price'], 2) : '&mdash;' ?></td>
                            <td><span class="badge <?= $badge ?>"><?= htmlspecialchars($req['status']) ?></span></td>
                            <td><?= $req['received_at'] ? htmlspecialchars(date('Y-m-d H:i', strtotime($req['received_at']))) : '&mdash;' ?></td>
                            <td><?= $req['requested_by'] ? htmlspecialchars($req['requested_by']) : '&mdash;' ?></td>
                            <td>
                                <?php if ($req['status'] === 'RECEIVED'): ?>
                                    <button type="button" class="btn btn-sm btn-secondary print-slip-btn" data-id="<?= (int) $req['id'] ?>" data-type="in">Print Slip</button>
                                <?php endif; ?>
                                <?php if ($isPending && ($_SESSION['role'] ?? '') === 'admin'): ?>
                                    <form method="post" action="/CIANANMRO/public/index.php?page=receive_request"
                                          onsubmit="return confirm('Mark request #<?= (int) $req['id'] ?> as received and add to stock on hand?');">
                                        <input type="hidden" name="request_id" value="<?= (int) $req['id'] ?>">
                                        <button type="submit" class="btn btn-sm btn-primary">Receive</button>
                                    </form>
                                    <form method="post" action="/CIANANMRO/public/index.php?page=cancel_request"
                                          onsubmit="return confirm('Cancel request #<?= (int) $req['id'] ?>?');">
                                        <input type="hidden" name="request_id" value="<?= (int) $req['id'] ?>">
                                        <button type="submit" class="btn btn-sm btn-secondary">Cancel</button>
                                    </form>
                                <?php elseif ($isPending): ?>
                                    <span class="badge badge-pending">AWAITING ADMIN</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Add Request Modal -->
<div class="modal-overlay" id="addRequestModal">
    <div class="modal">
        <div class="modal-header">
            <h2>Add Request</h2>
            <button type="button" class="modal-close" id="closeAddRequestModal">&times;</button>
        </div>
        <form method="post" action="/CIANANMRO/public/index.php?page=submit_request" class="modal-form"
              id="addRequestForm">
            <div class="form-field">
                <label for="requestItemLabel">Item</label>
                <input type="hidden" name="consumable_id" id="requestItemId">
                <div class="search-select" data-target-spec="requestItemSpec">
                    <input type="text" class="search-input" id="requestItemLabel" placeholder="Search item..." autocomplete="off">
                    <ul class="search-menu"></ul>
                </div>
            </div>

            <div class="form-field">
                <label for="requestItemSpec">Specification</label>
                <input type="text" id="requestItemSpec" readonly data-empty-label="&mdash;">
            </div>

            <div class="form-row">
                <div class="form-field">
                    <label for="requestQty">Quantity</label>
                    <input type="number" name="quantity" id="requestQty" min="1" required>
                </div>
                <div class="form-field">
                    <label for="requestUnitPrice">Price per <span id="requestUnitLabel">UOM</span></label>
                    <input type="number" name="unit_price" id="requestUnitPrice" min="0.01" step="0.01" required>
                </div>
            </div>

            <div class="form-field">
                <label for="requestTotal">Total (Price &times; Quantity)</label>
                <input type="text" id="requestTotal" readonly placeholder="&mdash;">
            </div>

            <div class="form-field">
                <label for="requestNotes">Notes</label>
                <input type="text" name="notes" id="requestNotes">
            </div>

            <div class="modal-actions">
                <button type="button" class="btn btn-secondary" id="cancelAddRequest">Cancel</button>
                <button type="submit" class="btn btn-primary">Submit Request</button>
            </div>
        </form>
    </div>
</div>

<script>
(function () {
    var items = [
        <?php foreach ($consumables as $it): ?>
        {
            id: <?= (int) $it['id'] ?>,
            code: <?= json_encode($it['item_code'], JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?>,
            name: <?= json_encode($it['item_name'], JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?>,
            spec: <?= json_encode((string) (($it['description'] ?: '') ?: $it['item_name']), JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?>,
            qty: <?= (int) $it['actual_stock_qty'] ?>,
            unit: <?= json_encode($it['uom'], JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?>,
            status: <?= json_encode($it['status'], JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?>
        },
        <?php endforeach; ?>
    ];

    var box = document.querySelector('#addRequestForm .search-select');
    var input = box.querySelector('.search-input');
    var menu = box.querySelector('.search-menu');
    var field = document.getElementById('requestItemId');
    var specInput = document.getElementById(box.dataset.targetSpec);
    var selectedId = null;

    function stockBadgeQty(item) {
        var cls = { 'IN STOCK': 'badge-ok', 'LOW STOCK': 'badge-warning', 'OUT OF STOCK': 'badge-out' }[item.status] || 'badge-ok';
        return '<span class="badge ' + cls + ' stock-badge">' + item.qty + ' ' + item.unit + '</span>';
    }

    function render(query) {
        menu.innerHTML = '';
        var q = (query || '').trim().toLowerCase();
        var matches = items.filter(function (it) {
            return it.name.toLowerCase().indexOf(q) !== -1 ||
                   it.code.toLowerCase().indexOf(q) !== -1 ||
                   it.spec.toLowerCase().indexOf(q) !== -1;
        });
        if (matches.length === 0) {
            menu.innerHTML = '<li class="search-empty">No matching items</li>';
            return;
        }
        matches.slice(0, 50).forEach(function (it) {
            var li = document.createElement('li');
            li.innerHTML = '<span class="search-name">' + it.code + ' &mdash; ' + it.name + '</span>' + stockBadgeQty(it);
            li.addEventListener('mousedown', function (e) {
                e.preventDefault();
                select(it);
            });
            menu.appendChild(li);
        });
    }

    function select(item) {
        selectedId = item.id;
        input.value = item.code;
        field.value = item.id;
        specInput.value = item.spec || specInput.dataset.emptyLabel || '';
        document.getElementById('requestUnitLabel').textContent = item.unit || 'UOM';
        menu.style.display = 'none';
    }

    // Live total = price per UOM x quantity
    var qtyInput = document.getElementById('requestQty');
    var priceInput = document.getElementById('requestUnitPrice');
    var totalOutput = document.getElementById('requestTotal');
    function updateTotal() {
        var q = parseInt(qtyInput.value, 10);
        var p = parseFloat(priceInput.value);
        totalOutput.value = (q > 0 && p > 0) ? 'P ' + (q * p).toFixed(2) : '';
    }
    qtyInput.addEventListener('input', updateTotal);
    priceInput.addEventListener('input', updateTotal);

    function openMenu() {
        var rect = input.getBoundingClientRect();
        menu.style.left = rect.left + 'px';
        menu.style.top = (rect.bottom + 4) + 'px';
        menu.style.minWidth = rect.width + 'px';
    }

    input.addEventListener('focus', function () {
        render('');
        openMenu();
        menu.style.display = 'block';
    });
    input.addEventListener('input', function () {
        selectedId = null;
        field.value = '';
        specInput.value = '';
        render(input.value);
        openMenu();
        menu.style.display = 'block';
    });
    input.addEventListener('blur', function () {
        setTimeout(function () { menu.style.display = 'none'; }, 150);
    });
    input.addEventListener('mousedown', function () {
        if (menu.style.display !== 'block') {
            render('');
            openMenu();
            menu.style.display = 'block';
        }
    });
    window.addEventListener('scroll', function () {
        if (menu.style.display === 'block') openMenu();
    }, true);
    render('');

    var modal = document.getElementById('addRequestModal');

    function openThermalSlip(id, type) {
        window.open(`/CIANANMRO/public/index.php?page=thermal_slip&id=${id}&type=${type}`, '_blank');
    }

    document.querySelectorAll('.print-slip-btn').forEach(function (btn) {
        btn.addEventListener('click', function () {
            openThermalSlip(btn.getAttribute('data-id'), btn.getAttribute('data-type'));
        });
    });

    document.getElementById('openAddRequestModal').addEventListener('click', function () {
        modal.classList.add('open');
    });
    document.getElementById('closeAddRequestModal').addEventListener('click', function () {
        modal.classList.remove('open');
    });
    document.getElementById('cancelAddRequest').addEventListener('click', function () {
        modal.classList.remove('open');
    });
    modal.addEventListener('click', function (e) {
        if (e.target === modal) modal.classList.remove('open');
    });
})();

// Table filtering & sorting
(function () {
    var table = document.getElementById('requestsTable');
    if (!table) return;
    var tbody  = table.tBodies[0];
    var rows   = Array.from(tbody.rows);
    var search = document.getElementById('inSearch');
    var from   = document.getElementById('inFrom');
    var to     = document.getElementById('inTo');
    var status = document.getElementById('inStatus');
    var item   = document.getElementById('inItem');

    function splitItemKey(value) {
        var parts = value.split('|');
        return { name: parts[0], code: parts[1] || '' };
    }

    function rowDate(txt) {
        var m = txt.match(/\d{4}-\d{2}-\d{2}/);
        return m ? m[0] : '';
    }

    function applyFilters() {
        var q  = (search.value || '').trim().toLowerCase();
        var f  = from.value;
        var t  = to.value;
        var st = status.value;
        var it = (item.value || '').trim();
        var sel = it ? splitItemKey(it) : null;
        rows.forEach(function (r) {
            var show = true;
            if (q && r.textContent.toLowerCase().indexOf(q) === -1) show = false;
            var d = rowDate(r.cells[1].textContent);
            if (f && (!d || d < f)) show = false;
            if (t && (!d || d > t)) show = false;
            if (st && r.cells[7].textContent.trim().toUpperCase() !== st) show = false;
            if (sel) {
                var itemText = r.cells[2].textContent.toLowerCase();
                if (sel.code && itemText.indexOf(sel.code.toLowerCase()) === -1) show = false;
            }
            r.style.display = show ? '' : 'none';
        });
    }

    search.addEventListener('input', applyFilters);
    from.addEventListener('change', applyFilters);
    to.addEventListener('change', applyFilters);
    status.addEventListener('change', applyFilters);
    item.addEventListener('change', applyFilters);

    table.querySelectorAll('th[data-sort]').forEach(function (th) {
        th.addEventListener('click', function () {
            table.querySelectorAll('th[data-sort]').forEach(function (o) {
                if (o !== th) delete o.dataset.dir;
            });
            th.dataset.dir = th.dataset.dir === 'asc' ? 'desc' : 'asc';
            var asc  = th.dataset.dir === 'asc' ? 1 : -1;
            var col  = parseInt(th.dataset.sort, 10);
            var type = th.dataset.type || 'text';

            rows.sort(function (a, b) {
                var av = a.cells[col].textContent.trim();
                var bv = b.cells[col].textContent.trim();
                var cmp;
                if (type === 'num') {
                    av = parseFloat(av.replace(/[^\d.]/g, '')) || 0;
                    bv = parseFloat(bv.replace(/[^\d.]/g, '')) || 0;
                    cmp = av - bv;
                } else if (type === 'date') {
                    av = rowDate(av);
                    bv = rowDate(bv);
                    cmp = av < bv ? -1 : av > bv ? 1 : a.index - b.index;
                } else {
                    cmp = av.localeCompare(bv);
                }
                return asc * cmp;
            });
            rows.forEach(function (r) { tbody.appendChild(r); });
        });
    });
})();
</script>
