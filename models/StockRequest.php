<?php

class StockRequest
{
    private PDO $db;

    public function __construct(PDO $db)
    {
        $this->db = $db;
    }

    public function getPendingRequests(): array
    {
        $sql = "SELECT sr.*, c.item_code, c.item_name, c.description AS item_description,
                       c.actual_stock_qty, c.uom AS item_unit
                FROM stock_requests sr
                JOIN consumables c ON c.id = sr.consumable_id
                WHERE sr.status = 'PENDING'
                ORDER BY sr.created_at DESC";

        return $this->db->query($sql)->fetchAll();
    }

    public function getRecent(int $limit = 10): array
    {
        $stmt = $this->db->prepare(
            "SELECT sr.*, c.item_code, c.item_name, c.uom AS item_unit
             FROM stock_requests sr
             JOIN consumables c ON c.id = sr.consumable_id
             ORDER BY sr.created_at DESC
             LIMIT :limit"
        );
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll();
    }

    public function getRecentReceived(int $limit = 10): array
    {
        $stmt = $this->db->prepare(
            "SELECT sr.*, c.item_code, c.item_name, c.uom AS item_unit
             FROM stock_requests sr
             JOIN consumables c ON c.id = sr.consumable_id
             WHERE sr.status = 'RECEIVED'
             ORDER BY sr.received_at DESC
             LIMIT :limit"
        );
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll();
    }

    public function getHistory(): array
    {
        $sql = "SELECT sr.*, c.item_code, c.item_name, c.description AS item_description, c.uom AS item_unit
                FROM stock_requests sr
                JOIN consumables c ON c.id = sr.consumable_id
                ORDER BY sr.created_at DESC
                LIMIT 200";

        return $this->db->query($sql)->fetchAll();
    }

    public function find(int $id): ?array
    {
        $stmt = $this->db->prepare(
            "SELECT sr.*, c.item_code, c.item_name, c.description AS item_description, c.uom AS item_unit
             FROM stock_requests sr
             JOIN consumables c ON c.id = sr.consumable_id
             WHERE sr.id = :id"
        );
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch();
        return $row !== false ? $row : null;
    }

    public function create(int $consumableId, int $quantity, string $notes, float $unitPrice, string $requestedBy = ''): void
    {
        $stmt = $this->db->prepare(
            'INSERT INTO stock_requests (consumable_id, quantity, unit_price, total_price, notes, requested_by, status)
             VALUES (:consumable_id, :qty, :unit_price, :total_price, :notes, :requested_by, :PENDING)'
        );

        $stmt->execute([
            ':consumable_id' => $consumableId,
            ':qty'           => $quantity,
            ':unit_price'    => $unitPrice,
            ':total_price'   => round($unitPrice * $quantity, 2),
            ':notes'         => $notes,
            ':requested_by'  => $requestedBy,
            ':PENDING'       => 'PENDING',
        ]);
    }

    public function receive(int $id): bool
    {
        $update = $this->db->prepare(
            "UPDATE stock_requests
             SET status = 'RECEIVED', received_at = NOW()
             WHERE id = :id AND status = 'PENDING'"
        );
        $update->execute([':id' => $id]);

        return $update->rowCount() > 0;
    }

    public function getDistinctItems(): array
    {
        $sql = "SELECT DISTINCT c.item_code, c.item_name
                FROM stock_requests sr
                JOIN consumables c ON c.id = sr.consumable_id
                ORDER BY c.item_name ASC";

        return $this->db->query($sql)->fetchAll();
    }

    public function cancel(int $id): void
    {
        $update = $this->db->prepare(
            "UPDATE stock_requests
             SET status = 'CANCELLED'
             WHERE id = :id AND status = 'PENDING'"
        );
        $update->execute([':id' => $id]);
    }
}
