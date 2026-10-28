<?php

require_once __DIR__ . '/../config/Database.php';
require_once __DIR__ . '/../models/Item.php';
require_once __DIR__ . '/../models/Consumable.php';
require_once __DIR__ . '/../models/StockRequest.php';
require_once __DIR__ . '/../models/Withdrawal.php';

class InventoryController
{
    private Item $item;
    private Consumable $consumable;
    private StockRequest $request;
    private Withdrawal $withdrawal;

    public function __construct()
    {
        $database = new Database();
        $this->item        = new Item($database->getConnection());
        $this->consumable  = new Consumable($database->getConnection());
        $this->request     = new StockRequest($database->getConnection());
        $this->withdrawal  = new Withdrawal($database->getConnection());
    }

    public function index(): void
    {
        $consumableStats = $this->consumable->getStatusCounts();

        $stats = [
            'total_items'    => $consumableStats['total'],
            'low_stock'      => $consumableStats['low_stock'],
            'out_of_stock'   => $consumableStats['out_of_stock'],
        ];

        $recentRequests    = $this->request->getRecent(10);
        $recentPurchases   = $this->request->getRecentReceived(10);
        $recentWithdrawals = $this->withdrawal->getRecentForDashboard(10);

        require __DIR__ . '/../views/items/index.php';
    }

    public function showStockForm(): void
    {
        $items = $this->item->getAllItems();
        require __DIR__ . '/../views/transactions/stock_form.php';
    }

    public function handleStockTransaction(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: /CIANANMRO/public/index.php?page=stock_form');
            exit;
        }

        $itemId   = (int) ($_POST['item_id'] ?? 0);
        $type     = ($_POST['transaction_type'] ?? '') === 'STOCK_OUT' ? 'STOCK_OUT' : 'STOCK_IN';
        $quantity = (int) ($_POST['quantity'] ?? 0);
        $notes    = trim($_POST['notes'] ?? '');

        if ($itemId <= 0 || $quantity <= 0) {
            $error = 'Please select an item and enter a valid quantity.';
            $items = $this->item->getAllItems();
            require __DIR__ . '/../views/transactions/stock_form.php';
            return;
        }

        try {
            $db = new Database();
            $pdo = $db->getConnection();

            $pdo->beginTransaction();

            if ($type === 'STOCK_IN') {
                $stmt = $pdo->prepare('UPDATE items SET quantity_on_hand = quantity_on_hand + :qty WHERE id = :id');
            } else {
                $stmt = $pdo->prepare('UPDATE items SET quantity_on_hand = quantity_on_hand - :qty WHERE id = :id AND quantity_on_hand >= :qty');
            }

            $stmt->execute([':qty' => $quantity, ':id' => $itemId]);

            if ($type === 'STOCK_OUT' && $stmt->rowCount() === 0) {
                $pdo->rollBack();
                $error = 'Not enough stock on hand for this transaction.';
                $items = $this->item->getAllItems();
                require __DIR__ . '/../views/transactions/stock_form.php';
                return;
            }

            $insert = $pdo->prepare(
                'INSERT INTO stock_transactions (item_id, transaction_type, quantity, notes, created_at)
                 VALUES (:item_id, :type, :qty, :notes, NOW())'
            );
            $insert->execute([
                ':item_id' => $itemId,
                ':type'    => $type,
                ':qty'     => $quantity,
                ':notes'   => $notes,
            ]);

            $pdo->commit();

            header('Location: /CIANANMRO/public/index.php?page=items&status=' . strtolower($type) . '_success');
            exit;
        } catch (PDOException $e) {
            if (isset($pdo) && $pdo->inTransaction()) {
                $pdo->rollBack();
            }
            $error = 'Database error: ' . $e->getMessage();
            $items = $this->item->getAllItems();
            require __DIR__ . '/../views/transactions/stock_form.php';
        }
    }
}
