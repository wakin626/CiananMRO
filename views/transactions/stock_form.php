<?php if (isset($error)): ?>
    <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
<?php endif; ?>

<h1 class="page-title">Log Stock Transaction</h1>

<form method="post" action="/CIANANMRO/public/index.php" class="stock-form">
    <input type="hidden" name="page" value="submit_stock">
    <input type="hidden" name="action" value="submit_stock">

    <label for="item_id">Item</label>
    <select name="item_id" id="item_id" required>
        <option value="">-- Select Item --</option>
        <?php foreach ($items as $item): ?>
            <option value="<?= (int) $item['id'] ?>">
                <?= htmlspecialchars($item['name']) ?> (On hand: <?= (int) $item['quantity_on_hand'] ?>)
            </option>
        <?php endforeach; ?>
    </select>

    <label for="transaction_type">Transaction Type</label>
    <select name="transaction_type" id="transaction_type" required>
        <option value="STOCK_IN">Stock-In</option>
        <option value="STOCK_OUT">Stock-Out</option>
    </select>

    <label for="quantity">Quantity</label>
    <input type="number" name="quantity" id="quantity" min="1" required>

    <label for="notes">Notes</label>
    <textarea name="notes" id="notes" rows="3"></textarea>

    <button type="submit">Log Transaction</button>
</form>
