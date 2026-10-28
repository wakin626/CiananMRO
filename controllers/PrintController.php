<?php

require_once __DIR__ . '/../config/Database.php';
require_once __DIR__ . '/../models/StockRequest.php';
require_once __DIR__ . '/../models/Withdrawal.php';

class PrintController
{
    private StockRequest $stockRequest;
    private Withdrawal $withdrawal;

    public function __construct()
    {
        $database = new Database();
        $db = $database->getConnection();
        $this->stockRequest = new StockRequest($db);
        $this->withdrawal = new Withdrawal($db);
    }

    public function thermalSlip(): void
    {
        $type = $_GET['type'] ?? '';
        $id = (int) ($_GET['id'] ?? 0);

        if ($id <= 0) {
            $this->renderNotFound('No record specified.');
            return;
        }

        if ($type === 'in') {
            $record = $this->stockRequest->find($id);
        } elseif ($type === 'out') {
            $record = $this->withdrawal->find($id);
        } else {
            $this->renderNotFound('Invalid slip type.');
            return;
        }

        if ($record === null) {
            $this->renderNotFound('The requested record does not exist.');
            return;
        }

        if ($type === 'in') {
            $slip = [
                'reference'    => 'RQ-000' . $record['id'],
                'title'        => 'STOCK IN',
                'subtitle'     => 'Receiving Slip',
                'date'         => $record['created_at'],
                'extra'        => $record['received_at'],
                'item_code'    => $record['item_code'],
                'item_name'    => $record['item_name'],
                'spec'         => $record['item_description'] ?: '',
                'quantity'     => (int) $record['quantity'],
                'uom'          => $record['item_unit'],
                'unit_price'   => number_format((float) $record['unit_price'], 2),
                'total_price'  => number_format((float) $record['total_price'], 2),
                'person'       => $record['requested_by'] ?: '—',
                'machine'      => '',
                'status'       => $record['status'],
                'priceSection' => true,
            ];
        } else {
            $slip = [
                'reference'    => 'WD-000' . $record['id'],
                'title'        => 'STOCK OUT',
                'subtitle'     => 'Withdrawal Slip',
                'date'         => $record['created_at'],
                'extra'        => null,
                'item_code'    => $record['item_code'],
                'item_name'    => $record['item_name'],
                'spec'         => $record['item_description'] ?: '',
                'quantity'     => (int) $record['quantity'],
                'uom'          => $record['item_unit'],
                'unit_price'   => null,
                'total_price'  => null,
                'person'       => $record['withdrawer'],
                'machine'      => $record['machine_equipment'] ?: '—',
                'status'       => null,
                'priceSection' => false,
            ];
        }

        require __DIR__ . '/../views/prints/thermal_slip.php';
    }

    private function renderNotFound(string $message): void
    {
        ?><!DOCTYPE html>
<html>
<head><title>Slip Not Found</title></head>
<body style="font-family: 'Segoe UI', Arial, sans-serif; text-align: center; padding-top: 60px; color: #555;">
    <p><strong>Slip not available</strong></p>
    <p><?= htmlspecialchars($message) ?></p>
    <p><button onclick="window.close()">Close</button></p>
</body>
</html><?php
    }
}
