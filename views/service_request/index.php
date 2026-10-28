<?php
$flashStatus = $_GET['status'] ?? '';
$flashMap = [
    'created'      => ['Service request created. It is now listed under Request.', 'success'],
    'assigned'     => ['Technician assigned &mdash; request moved to On Going.', 'success'],
    'completed'    => ['Request marked as completed.', 'success'],
    'create_error' => ['Could not save the service request &mdash; check the fields and try again.', 'danger'],
];
if (isset($flashMap[$flashStatus])):
    [$flashText, $flashType] = $flashMap[$flashStatus]; ?>
    <div class="alert alert-<?= $flashType ?>"><?= $flashText ?></div>
<?php endif; ?>

<h1 class="page-title">Service Request</h1>

<div class="toolbar">
    <button type="button" class="btn btn-primary" id="openSrModal">+ New Service Request</button>
</div>

<!-- Used by drag & drop for the ON GOING -> COMPLETED transition -->
<form method="post" action="/CIANANMRO/public/index.php?page=complete_service_request" id="completeForm"></form>

<div class="kanban-board" id="kanbanBoard">
    <?php foreach ($columns as $key => $col): ?>
        <div class="kanban-col" data-col="<?= htmlspecialchars($key) ?>">
            <div class="kanban-col-header kanban-col-<?= strtolower(str_replace(' ', '-', $key)) ?>">
                <span class="kanban-col-title"><?= htmlspecialchars($col['title']) ?></span>
                <span class="kanban-col-count"><?= count($col['cards']) ?></span>
            </div>
            <div class="kanban-col-body">
                <?php if (empty($col['cards'])): ?>
                    <div class="kanban-empty">No items here.</div>
                <?php endif; ?>
                <?php foreach ($col['cards'] as $card): ?>
                    <div class="kanban-card<?= in_array($key, ['REQUEST', 'ON GOING'], true) ? ' draggable' : '' ?>" draggable="<?= in_array($key, ['REQUEST', 'ON GOING'], true) ? 'true' : 'false' ?>" data-id="<?= (int) $card['id'] ?>" data-col="<?= htmlspecialchars($key) ?>">
                        <div class="kanban-card-top">
                            <span class="kanban-card-id">SR-<?= str_pad((string) $card['id'], 4, '0', STR_PAD_LEFT) ?></span>
                            <?php if ($card['severity']): ?>
                                <span class="kanban-sev sev-<?= strtolower($card['severity']) ?>"><?= htmlspecialchars($card['severity']) ?></span>
                            <?php endif; ?>
                        </div>
                        <?php if (empty($card['picture_path']) === false): ?>
                            <img class="kanban-card-pic" src="/CIANANMRO/<?= htmlspecialchars($card['picture_path']) ?>" alt="Request photo">
                        <?php endif; ?>
                        <div class="kanban-card-desc"><?= nl2br(htmlspecialchars($card['description'])) ?></div>
                        <?php if ($card['equipment_name'] || $card['machine_tag']): ?>
                            <div class="kanban-card-chips">
                                <?php if ($card['equipment_name']): ?>
                                    <span class="kanban-chip"><?= htmlspecialchars($card['equipment_name']) ?></span>
                                <?php endif; ?>
                                <?php if ($card['machine_tag']): ?>
                                    <span class="kanban-chip">Tag: <?= htmlspecialchars($card['machine_tag']) ?></span>
                                <?php endif; ?>
                                <?php if ($card['plant']): ?>
                                    <span class="kanban-chip"><?= htmlspecialchars($card['plant']) ?></span>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>
                        <div class="kanban-card-meta">
                            <span>By: <?= htmlspecialchars($card['requested_by']) ?><?= $card['department'] ? ' &mdash; ' . htmlspecialchars($card['department']) : '' ?></span>
                            <span><?= htmlspecialchars(date('Y-m-d', strtotime($card['created_at']))) ?><?= $card['location'] ? ' &middot; ' . htmlspecialchars($card['location']) : '' ?></span>
                        </div>
                        <?php if ($card['technician']): ?>
                            <div class="kanban-card-tech">Tech: <?= htmlspecialchars($card['technician']) ?></div>
                        <?php endif; ?>
                        <div class="kanban-card-actions">
                            <?php if ($key === 'REQUEST'): ?>
                                <button type="button" class="btn btn-sm btn-primary assign-btn" data-id="<?= (int) $card['id'] ?>">Assign Technician</button>
                            <?php elseif ($key === 'ON GOING'): ?>
                                <form method="post" action="/CIANANMRO/public/index.php?page=complete_service_request">
                                    <input type="hidden" name="id" value="<?= (int) $card['id'] ?>">
                                    <button type="submit" class="btn btn-sm btn-primary">Mark Done</button>
                                </form>
                            <?php else: ?>
                                <span class="kanban-done-date">Done: <?= htmlspecialchars(date('Y-m-d', strtotime($card['completed_at']))) ?></span>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endforeach; ?>
</div>

<!-- New Service Request Modal -->
<div class="modal-overlay" id="srModal">
    <div class="modal">
        <div class="modal-header">
            <h2>New Service Request</h2>
            <button type="button" class="modal-close" data-close-modal="srModal">&times;</button>
        </div>
        <form method="post" action="/CIANANMRO/public/index.php?page=create_service_request"
              enctype="multipart/form-data" class="modal-form" id="srForm">
            <div class="form-row">
                <div class="form-field">
                    <label for="sr_no">SR No.</label>
                    <input type="text" id="sr_no" value="SR-<?= str_pad((string) ($nextSrNo ?? 0), 4, '0', STR_PAD_LEFT) ?>" readonly>
                </div>
                <div class="form-field">
                    <label for="sr_datetime">Date &amp; Time</label>
                    <input type="text" id="sr_datetime" readonly required>
                </div>
            </div>

            <div class="form-row">
                <div class="form-field">
                    <label for="sr_requestor">Requestor</label>
                    <input type="text" name="requested_by" id="sr_requestor" value="<?= htmlspecialchars($_SESSION['full_name'] ?? '') ?>" required>
                </div>
                <div class="form-field">
                    <label for="sr_department">Department</label>
                    <select name="department" id="sr_department" required>
                        <option value="">-- Select department --</option>
                        <option value="HRA">HRA</option>
                        <option value="FINANCE">FINANCE</option>
                        <option value="WAREHOUSE">WAREHOUSE</option>
                        <option value="PRODUCTION">PRODUCTION</option>
                        <option value="QC">QC</option>
                        <option value="OTHERS">OTHERS</option>
                    </select>
                </div>
                <div class="form-field" id="sr_dept_others_field" style="display:none">
                    <label for="sr_department_custom">What department are you in?</label>
                    <input type="text" name="department_custom" id="sr_department_custom" maxlength="100" placeholder="e.g. LOGISTICS">
                </div>
            </div>

            <div class="form-row">
                <div class="form-field">
                    <label for="sr_location">Location / Area</label>
                    <input type="text" name="location" id="sr_location" required>
                </div>
                <div class="form-field">
                    <label for="sr_plant">Plant</label>
                    <select name="plant" id="sr_plant" required>
                        <option value="">-- Select plant --</option>
                        <option value="SPRING BOOK">Spring Book</option>
                        <option value="WILLYDRIVE">Willydrive</option>
                    </select>
                </div>
            </div>

            <div class="form-row">
                <div class="form-field">
                    <label for="sr_severity">Severity Level</label>
                    <select name="severity" id="sr_severity" required>
                        <option value="">-- Select severity --</option>
                        <option value="CRITICAL">Critical</option>
                        <option value="HIGH">High</option>
                        <option value="MODERATE">Moderate</option>
                        <option value="LOW">Low</option>
                    </select>
                </div>
                <div class="form-field">
                    <label for="sr_equipment">Equipment / Machine Name</label>
                    <input type="text" name="equipment_name" id="sr_equipment">
                </div>
            </div>

            <div class="form-field">
                <label for="sr_machine_tag">Machine Tag (if applicable)</label>
                <input type="text" name="machine_tag" id="sr_machine_tag">
            </div>

            <div class="form-field">
                <label for="sr_description">Request Description</label>
                <textarea name="description" id="sr_description" rows="4" placeholder="Describe the requested service / machine issue..." required></textarea>
            </div>

            <div class="form-field">
                <label for="sr_picture">Upload Picture (optional)</label>
                <input type="file" name="picture" id="sr_picture" accept=".jpg,.jpeg,.png,.gif,.webp">
            </div>

            <div class="modal-actions">
                <button type="button" class="btn btn-secondary" data-close-modal="srModal">Cancel</button>
                <button type="submit" class="btn btn-primary">Submit Request</button>
            </div>
        </form>
    </div>
</div>

<!-- Assign Technician Modal -->
<div class="modal-overlay" id="assignModal">
    <div class="modal">
        <div class="modal-header">
            <h2>Assign Technician</h2>
            <button type="button" class="modal-close" data-close-modal="assignModal">&times;</button>
        </div>
        <form method="post" action="/CIANANMRO/public/index.php?page=assign_service_request" class="modal-form">
            <input type="hidden" name="id" id="assign_sr_id">
            <div class="form-field">
                <label for="assign_technician">Maintenance Technician</label>
                <select name="technician" id="assign_technician" required>
                    <option value="">-- Select a technician --</option>
                    <?php foreach ($technicians as $t): ?>
                        <option value="<?= htmlspecialchars($t['full_name']) ?>"><?= htmlspecialchars($t['full_name']) ?></option>
                    <?php endforeach; ?>
                </select>
                <?php if (empty($technicians)): ?>
                    <p class="upload-note">No maintenance technicians registered yet. Users must register with the &ldquo;Maintenance Technician&rdquo; role.</p>
                <?php endif; ?>
            </div>
            <div class="modal-actions">
                <button type="button" class="btn btn-secondary" data-close-modal="assignModal">Cancel</button>
                <button type="submit" class="btn btn-primary" <?= empty($technicians) ? 'disabled' : '' ?>>Assign</button>
            </div>
        </form>
    </div>
</div>

<script>
(function () {
    function openModal(id) {
        document.getElementById(id).classList.add('open');
        if (id === 'assignModal') {
            document.getElementById('assign_technician').focus();
        }
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
            if (e.target === overlay) closeModal(overlay);
        });
    });

    document.getElementById('openSrModal').addEventListener('click', function () {
        // Auto-fill SR no. and current date/time every time the modal opens
        var now = new Date();
        function two(n) { return n < 10 ? '0' + n : n; }
        document.getElementById('sr_datetime').value =
            now.getFullYear() + '-' + two(now.getMonth() + 1) + '-' + two(now.getDate()) +
            ' ' + two(now.getHours()) + ':' + two(now.getMinutes());
        document.getElementById('sr_picture').value = '';
        openModal('srModal');
    });

    // Department OTHERS: reveal the free-text department input
    var deptSelect = document.getElementById('sr_department');
    var deptOthersField = document.getElementById('sr_dept_others_field');
    var deptCustom = document.getElementById('sr_department_custom');

    deptSelect.addEventListener('change', function () {
        var showOthers = deptSelect.value === 'OTHERS';
        deptOthersField.style.display = showOthers ? '' : 'none';
        deptCustom.required = showOthers;
        if (!showOthers) deptCustom.value = '';
    });

    document.querySelectorAll('.assign-btn').forEach(function (btn) {
        btn.addEventListener('click', function () {
            document.getElementById('assign_sr_id').value = btn.getAttribute('data-id');
            openModal('assignModal');
        });
    });

    // ===== Drag & drop (forward-only: REQUEST -> ON GOING -> COMPLETED) =====
    var board = document.getElementById('kanbanBoard');
    var completeForm = document.getElementById('completeForm');
    var COL_ORDER = { 'REQUEST': 0, 'ON GOING': 1, 'COMPLETED': 2 };
    var dragged = null;

    board.addEventListener('dragstart', function (e) {
        var card = e.target.closest('.kanban-card');
        if (!card || card.getAttribute('draggable') !== 'true') {
            e.preventDefault();
            return;
        }
        dragged = card;
        card.classList.add('dragging');
        try { e.dataTransfer.setData('text/plain', card.getAttribute('data-id')); } catch (err) {}
        e.dataTransfer.effectAllowed = 'move';
    });

    board.addEventListener('dragend', function () {
        if (dragged) { dragged.classList.remove('dragging'); dragged = null; }
        board.querySelectorAll('.kanban-col').forEach(function (col) {
            col.classList.remove('drop-ok', 'drop-deny');
        });
    });

    board.addEventListener('dragover', function (e) {
        if (!dragged) return;
        var col = e.target.closest('.kanban-col');
        board.querySelectorAll('.kanban-col').forEach(function (c) { c.classList.remove('drop-ok', 'drop-deny'); });
        if (!col || col === dragged.closest('.kanban-col')) { e.preventDefault(); return; }

        var from = COL_ORDER[dragged.getAttribute('data-col')];
        var to = COL_ORDER[col.getAttribute('data-col')];
        if (to === from + 1) {
            // One step forward only: still requires completing the needed data on drop
            e.preventDefault();
            e.dataTransfer.dropEffect = 'move';
            col.classList.add('drop-ok');
        } else {
            // Backward or same column: not allowed
            e.dataTransfer.dropEffect = 'none';
            col.classList.add('drop-deny');
        }
    });

    board.addEventListener('drop', function (e) {
        if (!dragged) return;
        var col = e.target.closest('.kanban-col');
        if (!col || col === dragged.closest('.kanban-col')) return;

        var from = COL_ORDER[dragged.getAttribute('data-col')];
        var to = COL_ORDER[col.getAttribute('data-col')];
        if (to <= from) return; // deny drop silently

        e.preventDefault();
        var cardId = dragged.getAttribute('data-id');

        if (to === 1) {
            // REQUEST -> ON GOING: needs the technician (assign modal)
            document.getElementById('assign_sr_id').value = cardId;
            openModal('assignModal');
        } else if (to === 2) {
            // ON GOING -> COMPLETED: confirm, then submit completion
            if (confirm('Mark ' + 'SR-' + String(cardId).padStart(4, '0') + ' as DONE?')) {
                completeForm.innerHTML = '<input type="hidden" name="id" value="' + cardId + '">';
                completeForm.submit();
            }
        }
    });
})();
</script>
