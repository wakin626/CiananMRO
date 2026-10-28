<?php

require_once __DIR__ . '/../config/Database.php';
require_once __DIR__ . '/../models/Consumable.php';
require_once __DIR__ . '/../models/StockRequest.php';
require_once __DIR__ . '/../models/Withdrawal.php';

class StockController
{
    private PDO $pdo;
    private Consumable $consumable;
    private StockRequest $request;
    private Withdrawal $withdrawal;

    public function __construct()
    {
        $database = new Database();
        $this->pdo        = $database->getConnection();
        $this->consumable = new Consumable($this->pdo);
        $this->request    = new StockRequest($this->pdo);
        $this->withdrawal = new Withdrawal($this->pdo);
    }

    public function showStockIn(): void
    {
        $consumables     = $this->consumable->getAll();
        $pendingRequests = $this->request->getPendingRequests();
        $requestHistory  = $this->request->getHistory();
        $requestItems    = $this->request->getDistinctItems();

        require __DIR__ . '/../views/transactions/stock_in.php';
    }

    public function showStockOut(): void
    {
        $consumables    = $this->consumable->getAll();
        $withdrawals    = $this->withdrawal->getRecent();
        $nextWithdrawNo = $this->nextWithdrawNumber();

        require __DIR__ . '/../views/transactions/stock_out.php';
    }

    public function submitRequest(): void
    {
        $consumableId = (int) ($_POST['consumable_id'] ?? 0);
        $quantity = (int) ($_POST['quantity'] ?? 0);
        $unitPrice = (float) ($_POST['unit_price'] ?? 0);
        $notes    = trim($_POST['notes'] ?? '');

        if ($consumableId <= 0 || $quantity <= 0 || $unitPrice <= 0) {
            header('Location: /CIANANMRO/public/index.php?page=stock_in&status=request_error');
            exit;
        }

        try {
            $this->request->create($consumableId, $quantity, $notes, $unitPrice, trim($_SESSION['full_name'] ?? ''));
        } catch (PDOException $e) {
            header('Location: /CIANANMRO/public/index.php?page=stock_in&status=request_error');
            exit;
        }

        header('Location: /CIANANMRO/public/index.php?page=stock_in&status=request_success');
        exit;
    }

    public function receiveRequest(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: /CIANANMRO/public/index.php?page=stock_in');
            exit;
        }

        if (($_SESSION['role'] ?? '') !== 'admin') {
            header('Location: /CIANANMRO/public/index.php?page=stock_in&status=request_forbidden');
            exit;
        }

        $id = (int) ($_POST['request_id'] ?? 0);

        try {
            $this->pdo->beginTransaction();

            $row = $this->request->receive($id);

            if ($row === false) {
                $this->pdo->rollBack();
                header('Location: /CIANANMRO/public/index.php?page=stock_in&status=request_error');
                exit;
            }

            $fetch = $this->pdo->prepare('SELECT consumable_id, quantity FROM stock_requests WHERE id = :id');
            $fetch->execute([':id' => $id]);
            $requestRow = $fetch->fetch();

            if ($requestRow === false) {
                $this->pdo->rollBack();
                header('Location: /CIANANMRO/public/index.php?page=stock_in&status=request_error');
                exit;
            }

            $get = $this->pdo->prepare('SELECT actual_stock_qty, low_level_qty FROM consumables WHERE id = :id');
            $get->execute([':id' => (int) $requestRow['consumable_id']]);
            $consumableRow = $get->fetch();

            if ($consumableRow === false) {
                $this->pdo->rollBack();
                header('Location: /CIANANMRO/public/index.php?page=stock_in&status=request_error');
                exit;
            }

            $newQty = (int) $consumableRow['actual_stock_qty'] + (int) $requestRow['quantity'];

            $this->pdo->prepare(
                'UPDATE consumables SET actual_stock_qty = :qty, status = :status WHERE id = :id'
            )->execute([
                ':qty'    => $newQty,
                ':status' => Consumable::calculateStatus($newQty, (int) $consumableRow['low_level_qty']),
                ':id'     => (int) $requestRow['consumable_id'],
            ]);

            $this->pdo->commit();
        } catch (PDOException $e) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            header('Location: /CIANANMRO/public/index.php?page=stock_in&status=request_error');
            exit;
        }

        header('Location: /CIANANMRO/public/index.php?page=stock_in&status=request_received_success');
        exit;
    }

    public function cancelRequest(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: /CIANANMRO/public/index.php?page=stock_in');
            exit;
        }

        if (($_SESSION['role'] ?? '') !== 'admin') {
            header('Location: /CIANANMRO/public/index.php?page=stock_in&status=request_forbidden');
            exit;
        }

        $id = (int) ($_POST['request_id'] ?? 0);

        try {
            $this->request->cancel($id);
        } catch (PDOException $e) {
            header('Location: /CIANANMRO/public/index.php?page=stock_in&status=request_error');
            exit;
        }

        header('Location: /CIANANMRO/public/index.php?page=stock_in&status=request_cancel_success');
        exit;
    }

    public function submitWithdrawal(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: /CIANANMRO/public/index.php?page=stock_out');
            exit;
        }

        $consumableId = (int) ($_POST['consumable_id'] ?? 0);
        $quantity  = (int) ($_POST['quantity'] ?? 0);
        $machine   = trim($_POST['machine_equipment'] ?? '');
        $withdrawer = trim($_SESSION['full_name'] ?? ($_POST['withdrawer'] ?? ''));

        if ($consumableId <= 0 || $quantity <= 0 || $withdrawer === '') {
            header('Location: /CIANANMRO/public/index.php?page=stock_out&status=withdraw_error');
            exit;
        }

        try {
            $this->pdo->beginTransaction();

            $get = $this->pdo->prepare('SELECT actual_stock_qty, low_level_qty FROM consumables WHERE id = :id');
            $get->execute([':id' => $consumableId]);
            $consumableRow = $get->fetch();

            if ($consumableRow === false) {
                $this->pdo->rollBack();
                header('Location: /CIANANMRO/public/index.php?page=stock_out&status=withdraw_error');
                exit;
            }

            if ((int) $consumableRow['actual_stock_qty'] < $quantity) {
                $this->pdo->rollBack();
                header('Location: /CIANANMRO/public/index.php?page=stock_out&status=withdraw_insufficient');
                exit;
            }

            $newQty = (int) $consumableRow['actual_stock_qty'] - $quantity;

            $this->pdo->prepare(
                'UPDATE consumables SET actual_stock_qty = :qty, status = :status WHERE id = :id'
            )->execute([
                ':qty'    => $newQty,
                ':status' => Consumable::calculateStatus($newQty, (int) $consumableRow['low_level_qty']),
                ':id'     => $consumableId,
            ]);

            $this->withdrawal->create($consumableId, $quantity, $machine, $withdrawer);

            $this->pdo->commit();
        } catch (PDOException $e) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            header('Location: /CIANANMRO/public/index.php?page=stock_out&status=withdraw_error');
            exit;
        }

        header('Location: /CIANANMRO/public/index.php?page=stock_out&status=withdraw_success');
        exit;
    }

    private function nextWithdrawNumber(): int
    {
        $stmt = $this->pdo->query('SELECT COALESCE(MAX(id), 0) + 1 FROM withdrawals');
        return (int) $stmt->fetchColumn();
    }
}

