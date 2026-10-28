<?php

class Withdrawal
{
    private PDO $db;

    public function __construct(PDO $db)
    {
        $this->db = $db;
    }

    public function getRecent(): array
    {
        $sql = "SELECT w.*, c.item_code, c.item_name, c.description AS item_description,
                       c.uom AS item_unit
                FROM withdrawals w
                JOIN consumables c ON c.id = w.consumable_id
                ORDER BY w.created_at DESC
                LIMIT 200";

        return $this->db->query($sql)->fetchAll();
    }

    public function getRecentForDashboard(int $limit = 10): array
    {
        $stmt = $this->db->prepare(
            "SELECT w.*, c.item_code, c.item_name, c.uom AS item_unit
             FROM withdrawals w
             JOIN consumables c ON c.id = w.consumable_id
             ORDER BY w.created_at DESC
             LIMIT :limit"
        );
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll();
    }

    public function find(int $id): ?array
    {
        $stmt = $this->db->prepare(
            "SELECT w.*, c.item_code, c.item_name, c.description AS item_description, c.uom AS item_unit
             FROM withdrawals w
             JOIN consumables c ON c.id = w.consumable_id
             WHERE w.id = :id"
        );
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch();
        return $row !== false ? $row : null;
    }

    public function create(int $consumableId, int $quantity, string $machine, string $withdrawer): void
    {
        $stmt = $this->db->prepare(
            'INSERT INTO withdrawals (consumable_id, machine_equipment, quantity, withdrawer)
             VALUES (:consumable_id, :machine, :qty, :withdrawer)'
        );

        $stmt->execute([
            ':consumable_id' => $consumableId,
            ':machine'       => $machine,
            ':qty'           => $quantity,
            ':withdrawer'    => $withdrawer,
        ]);
    }
}
