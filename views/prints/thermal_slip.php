<?php
/** @var array $slip */
$dateText = date('Y-m-d', strtotime($slip['date']));
$timeText = date('H:i', strtotime($slip['date']));
$receivedText = !empty($slip['extra']) ? date('Y-m-d H:i', strtotime($slip['extra'])) : null;
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title><?= htmlspecialchars($slip['reference']) ?> — <?= htmlspecialchars($slip['title']) ?> Slip</title>
<style>
    @page {
        size: 80mm auto;
        margin: 2mm;
    }

    * {
        margin: 0;
        padding: 0;
        box-sizing: border-box;
    }

    body {
        font-family: "Consolas", "Courier New", monospace;
        font-size: 12px;
        color: #000;
        background: #fff;
        width: 78mm;
        padding: 1mm 2mm;
    }

    .top {
        text-align: center;
        margin-bottom: 6px;
    }

    .brand {
        font-size: 16px;
        font-weight: bold;
        letter-spacing: 1px;
    }

    .subtitle {
        font-size: 11px;
        margin-top: 2px;
    }

    .divider {
        border-top: 1px dashed #000;
        margin: 5px 0;
    }

    .line {
        display: flex;
        justify-content: space-between;
        gap: 6px;
        margin: 2px 0;
    }

    .line .label {
        flex-shrink: 0;
    }

    .line .value,
    .line .value-wrap {
        text-align: right;
        word-break: break-word;
    }

    .wrap {
        margin: 2px 0;
    }

    .wrap .label {
        font-weight: bold;
    }

    .totals {
        margin-top: 4px;
    }

    .totals .line .value {
        font-weight: bold;
    }

    .qty {
        font-size: 16px;
        font-weight: bold;
        text-align: center;
        margin: 6px 0;
    }

    .footnote {
        text-align: center;
        font-size: 10px;
        margin-top: 8px;
    }

    .print-bar {
        font-family: "Segoe UI", Arial, sans-serif;
        text-align: center;
        margin-bottom: 8px;
    }

    .print-bar button {
        font-size: 13px;
        padding: 6px 18px;
        cursor: pointer;
        border: 1px solid #444;
        background: #444;
        color: #fff;
        border-radius: 4px;
    }

    .print-bar button:hover {
        background: #222;
    }

    @media print {
        .print-bar {
            display: none;
        }
    }
</style>
</head>
<body>
    <div class="print-bar">
        <button type="button" onclick="window.print()">Print Now</button>
    </div>

    <div class="top">
        <div class="brand">CIANAN MRO</div>
        <div class="subtitle"><?= htmlspecialchars($slip['title']) ?> — <?= htmlspecialchars($slip['subtitle']) ?></div>
    </div>

    <div class="divider"></div>

    <div class="line"><span class="label">No.</span><span class="value"><?= htmlspecialchars($slip['reference']) ?></span></div>
    <div class="line"><span class="label">Date</span><span class="value"><?= htmlspecialchars($dateText) ?></span></div>
    <div class="line"><span class="label">Time</span><span class="value"><?= htmlspecialchars($timeText) ?></span></div>
    <?php if ($receivedText !== null): ?>
        <div class="line"><span class="label">Received</span><span class="value"><?= htmlspecialchars($receivedText) ?></span></div>
    <?php endif; ?>
    <?php if ($slip['status'] !== null): ?>
        <div class="line"><span class="label">Status</span><span class="value"><?= htmlspecialchars($slip['status']) ?></span></div>
    <?php endif; ?>

    <div class="divider"></div>

    <div class="wrap">
        <span class="label">Item Code:</span>
        <span class="value"><?= htmlspecialchars($slip['item_code']) ?></span>
    </div>
    <div class="wrap">
        <span class="label">Description:</span>
        <span class="value"><?= htmlspecialchars($slip['item_name']) ?></span>
    </div>
    <?php if ($slip['spec'] !== ''): ?>
        <div class="wrap">
            <span class="label">Spec:</span>
            <span class="value"><?= nl2br(htmlspecialchars($slip['spec'])) ?></span>
        </div>
    <?php endif; ?>

    <div class="divider"></div>

    <div class="line"><span class="label">Qty</span><span class="value"><?= (int) $slip['quantity'] ?> <?= htmlspecialchars($slip['uom']) ?></span></div>

    <?php if ($slip['priceSection']): ?>
        <div class="line totals"><span class="label">Unit Price</span><span class="value"><?= htmlspecialchars($slip['unit_price']) ?></span></div>
        <div class="line totals"><span class="label">TOTAL</span><span class="value"><?= htmlspecialchars($slip['total_price']) ?></span></div>
    <?php endif; ?>

    <?php if ($slip['machine'] !== '' && $slip['machine'] !== '—'): ?>
        <div class="divider"></div>
        <div class="wrap">
            <span class="label">Machine/Equip:</span>
            <span class="value"><?= htmlspecialchars($slip['machine']) ?></span>
        </div>
    <?php elseif ($slip['machine'] !== ''): ?>
        <div class="wrap">
            <span class="label">Machine/Equip:</span>
            <span class="value">—</span>
        </div>
    <?php endif; ?>

    <div class="divider"></div>

    <div class="wrap">
        <span class="label">Withdrawn / Requested By:</span>
        <span class="value"><?= htmlspecialchars($slip['person']) ?></span>
    </div>

    <div class="footnote">Thank you! Keep this slip for your records.</div>
</body>
</html>
