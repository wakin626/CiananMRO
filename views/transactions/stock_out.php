<?php
$flashStatus = $_GET['status'] ?? '';
$flashMap = [
    'withdraw_success'       => ['Withdrawal logged successfully.', 'success'],
    'withdraw_insufficient'  => ['Cannot withdraw &mdash; the item does not have enough stock on hand for that quantity. Check the SOH badge in the dropdown and try again.', 'danger'],
    'withdraw_error'         => ['Invalid withdrawal &mdash; check the item, quantity, and withdrawer.', 'danger'],
];
if (isset($flashMap[$flashStatus])):
    [$flashText, $flashType] = $flashMap[$flashStatus]; ?>
    <div class="alert alert-<?= $flashType ?>"><?= $flashText ?></div>
<?php elseif (isset($error)): ?>
    <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
<?php endif; ?>

<h1 class="page-title">Stock Out &mdash; Withdrawal</h1>

<div class="filter-toolbar">
    <input type="search" id="outSearch" placeholder="Search by withdraw no., item, code, or withdrawer...">
    <div class="filter-inline">
        <label for="outFrom">From</label>
        <input type="date" id="outFrom">
    </div>
    <div class="filter-inline">
        <label for="outTo">To</label>
        <input type="date" id="outTo">
    </div>
    <select id="outItem">
        <option value="">All Items</option>
        <?php
        $wrItemNames = [];
        foreach ($withdrawals as $w) {
            $wrItemNames[$w['item_name'] . '|' . $w['item_code']] = $w['item_name'];
        }
        ksort($wrItemNames);
        foreach ($wrItemNames as $key => $name): ?>
            <option value="<?= htmlspecialchars($key) ?>"><?= htmlspecialchars($name) ?></option>
        <?php endforeach; ?>
    </select>
    <select id="outWithdrawer">
        <option value="">All Withdrawers</option>
        <?php
        $withdrawers = array_filter(array_unique(array_map(
            fn ($w) => (string) $w['withdrawer'],
            $withdrawals
        )));
        sort($withdrawers);
        foreach ($withdrawers as $p): ?>
            <option value="<?= htmlspecialchars($p) ?>"><?= htmlspecialchars($p) ?></option>
        <?php endforeach; ?>
    </select>
</div>

<div class="card">
    <div class="card-header card-header-flex">
        <span>Withdrawals</span>
        <button type="button" class="btn btn-primary" id="openAddWithdrawModal" onclick="openModal('addWithdrawModal')">+ Log Withdrawal</button>
    </div>
    <div class="card-body">
        <table class="data-table" id="withdrawalsTable">
            <thead>
                <tr>
                    <th data-sort="0" data-type="num">Withdraw No.</th>
                    <th data-sort="1" data-type="date">Date</th>
                    <th>Time</th>
                    <th data-sort="3">Machine / Equipment</th>
                    <th>Item</th>
                    <th>Specification</th>
                    <th data-sort="6" data-type="num">Qty</th>
                    <th data-sort="7">Withdrawer</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($withdrawals)): ?>
                    <tr><td colspan="9" class="empty">No withdrawals yet.</td></tr>
                <?php else: ?>
                    <?php foreach ($withdrawals as $w): ?>
                        <tr>
                            <td>WD-000<?= (int) $w['id'] ?></td>
                            <td><?= htmlspecialchars(date('Y-m-d', strtotime($w['created_at']))) ?></td>
                            <td><?= htmlspecialchars(date('H:i', strtotime($w['created_at']))) ?></td>
                            <td><?= $w['machine_equipment'] ? htmlspecialchars($w['machine_equipment']) : '&mdash;' ?></td>
                            <td><?= htmlspecialchars($w['item_code']) ?> &mdash; <?= htmlspecialchars($w['item_name']) ?></td>
                            <td><?= htmlspecialchars($w['item_description'] ?: $w['item_name']) ?></td>
                            <td><?= (int) $w['quantity'] . ' ' . htmlspecialchars($w['item_unit']) ?></td>
                            <td><?= htmlspecialchars($w['withdrawer']) ?></td>
                            <td>
                                <button type="button" class="btn btn-sm btn-secondary print-slip-btn" data-id="<?= (int) $w['id'] ?>" data-type="out">Print Slip</button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Log Withdrawal Modal -->
<div class="modal-overlay" id="addWithdrawModal">
    <div class="modal">
        <div class="modal-header">
            <h2>Log Withdrawal</h2>
            <button type="button" class="modal-close" onclick="closeModal('addWithdrawModal')">&times;</button>
        </div>
        <form method="post" action="/CIANANMRO/public/index.php?page=submit_withdrawal"
              class="modal-form" id="withdrawalForm">
            <div class="alert alert-danger" id="withdrawInlineError" style="display:none"></div>

            <div class="form-row">
                <div class="form-field">
                    <label for="withdrawNo">Withdraw No.</label>
                    <input type="text" id="withdrawNo" value="WD-000<?= (int) $nextWithdrawNo ?>" readonly>
                </div>
                <div class="form-field">
                    <label for="withdrawDate">Date &amp; Time of Withdrawal</label>
                    <input type="text" id="withdrawDate" readonly required>
                </div>
            </div>

            <div class="form-field">
                <label for="withdrawMachine">Machine / Equipment / Purpose</label>
                <input type="text" name="machine_equipment" id="withdrawMachine" placeholder="Machine / Equipment / Purpose">
            </div>

            <div class="form-field">
                <label for="withdrawItemLabel">Item <span id="withdrawStockBadge"></span></label>
                <input type="hidden" name="consumable_id" id="withdrawItemId">
                <div class="search-select" data-target-spec="withdrawSpec">
                    <input type="text" class="search-input" id="withdrawItemLabel" placeholder="Search item..." autocomplete="off">
                    <ul class="search-menu"></ul>
                </div>
            </div>

            <div class="form-field">
                <label for="withdrawSpec">Specification</label>
                <input type="text" id="withdrawSpec" readonly data-empty-label="&mdash;">
            </div>

            <div class="form-row">
                <div class="form-field">
                    <label for="withdrawQty">Qty of Withdrawal</label>
                    <input type="number" name="quantity" id="withdrawQty" min="1" required>
                </div>
                <div class="form-field">
                    <label for="withdrawer">Withdrawer</label>
                    <input type="text" name="withdrawer" id="withdrawer"
                           value="<?= htmlspecialchars($_SESSION['full_name'] ?? '') ?>"
                           readonly title="Withdrawer is the logged-in user">
                </div>
            </div>

            <div class="modal-actions">
                <button type="button" class="btn btn-secondary" id="cancelAddWithdraw" onclick="closeModal('addWithdrawModal')">Cancel</button>
                <button type="submit" class="btn btn-primary">Log Withdrawal</button>
            </div>
        </form>
    </div>
</div>

<script>
function openModal(id) {
    document.getElementById(id).classList.add('open');
}
function closeModal(id) {
    document.getElementById(id).classList.remove('open');
}

function openThermalSlip(id, type) {
    window.open(`/CIANANMRO/public/index.php?page=thermal_slip&id=${id}&type=${type}`, '_blank');
}

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

    var box = document.querySelector('#withdrawalForm .search-select');
    var input = box.querySelector('.search-input');
    var menu = box.querySelector('.search-menu');
    var field = document.getElementById('withdrawItemId');
    var specInput = document.getElementById('withdrawSpec');
    var qtyInput = document.getElementById('withdrawQty');
    var inlineError = document.getElementById('withdrawInlineError');
    var selectedId = null;
    var selectedItem = null;
    var stockBadge = document.getElementById('withdrawStockBadge');

    function badgeMarkup(item) {
        var cls = { 'IN STOCK': 'badge-ok', 'LOW STOCK': 'badge-warning', 'OUT OF STOCK': 'badge-out' }[item.status] || 'badge-ok';
        return '<span class="badge ' + cls + ' stock-badge" id="withdrawAvailableBadge">' +
            'Available: ' + item.qty + ' ' + item.unit + '</span>';
    }

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

    function clearInlineError() {
        inlineError.style.display = 'none';
        inlineError.textContent = '';
    }

    function select(item) {
        selectedId = item.id;
        selectedItem = item;
        input.value = item.code;
        field.value = item.id;
        specInput.value = item.spec || specInput.dataset.emptyLabel || '';
        qtyInput.max = item.qty;
        stockBadge.innerHTML = badgeMarkup(item);
        clearInlineError();
        menu.style.display = 'none';
    }

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
        selectedItem = null;
        field.value = '';
        specInput.value = '';
        stockBadge.innerHTML = '';
        qtyInput.removeAttribute('max');
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

    // Block the submission when withdrawing more than the available stock
    var form = document.getElementById('withdrawalForm');
    form.addEventListener('submit', function (e) {
        var qty = parseInt(qtyInput.value, 10);
        if (selectedItem && (!qty || qty < 1)) {
            e.preventDefault();
            inlineError.textContent = 'Please enter a valid withdrawal quantity.';
            inlineError.style.display = 'block';
            return;
        }
        if (selectedItem && qty > selectedItem.qty) {
            e.preventDefault();
            inlineError.textContent = 'Cannot withdraw ' + qty + ' ' + selectedItem.unit +
                ' \u2014 only ' + selectedItem.qty + ' ' + selectedItem.unit +
                ' in stock for ' + selectedItem.code + '. Please request a stock-in instead.';
            inlineError.style.display = 'block';
        }
    });
    // Live restriction: cannot exceed the selected item's available stock
    qtyInput.addEventListener('input', function () {
        clearInlineError();
        if (!selectedItem) return;
        var qty = parseInt(qtyInput.value, 10);
        if (qty && qty > selectedItem.qty) {
            inlineError.textContent = 'Only ' + selectedItem.qty + ' ' + selectedItem.unit +
                ' of ' + selectedItem.code + ' available in stock. Please request a stock-in instead.';
            inlineError.style.display = 'block';
        }
    });

    // Current date & time for the withdrawal
    var now = new Date();
    function two(n) { return n < 10 ? '0' + n : n; }
    document.getElementById('withdrawDate').value =
        now.getFullYear() + '-' + two(now.getMonth() + 1) + '-' + two(now.getDate()) +
        ' ' + two(now.getHours()) + ':' + two(now.getMinutes());

    document.querySelectorAll('.print-slip-btn').forEach(function (btn) {
        btn.addEventListener('click', function () {
            openThermalSlip(btn.getAttribute('data-id'), btn.getAttribute('data-type'));
        });
    });
})();

// Table filtering & sorting
(function () {
    var table = document.getElementById('withdrawalsTable');
    if (!table) return;
    var tbody  = table.tBodies[0];
    var rows   = Array.from(tbody.rows);
    var search = document.getElementById('outSearch');
    var from   = document.getElementById('outFrom');
    var to     = document.getElementById('outTo');
    var item   = document.getElementById('outItem');
    var withdrawer = document.getElementById('outWithdrawer');

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
        var it  = (item.value || '').trim().toLowerCase();
        var wr  = (withdrawer.value || '').trim().toLowerCase();
        var sel = it ? splitItemKey(it) : null;
        rows.forEach(function (r) {
            var show = true;
            if (q && r.textContent.toLowerCase().indexOf(q) === -1) show = false;
            var d = rowDate(r.cells[1].textContent);
            if (f && (!d || d < f)) show = false;
            if (t && (!d || d > t)) show = false;
            if (sel) {
                var itemText = r.cells[4].textContent.toLowerCase();
                if ((sel.code && itemText.indexOf(sel.code.toLowerCase()) === -1) ||
                    (sel.name && itemText.indexOf(sel.name.toLowerCase()) === -1)) show = false;
            }
            if (wr && r.cells[7].textContent.trim().toLowerCase() !== wr) show = false;
            r.style.display = show ? '' : 'none';
        });
    }

    search.addEventListener('input', applyFilters);
    from.addEventListener('change', applyFilters);
    to.addEventListener('change', applyFilters);
    item.addEventListener('change', applyFilters);
    withdrawer.addEventListener('change', applyFilters);

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
