<?php

class Item
{
    private PDO $db;

    public function __construct(PDO $db)
    {
        $this->db = $db;
    }

    public function getAllItems(): array
    {
        $sql = "SELECT i.*, c.name AS category_name
                FROM items i
                LEFT JOIN categories c ON i.category_id = c.id
                ORDER BY i.name ASC";

        $stmt = $this->db->query($sql);
        return $stmt->fetchAll();
    }

    public function getLowStockItems(): array
    {
        $sql = "SELECT i.*, c.name AS category_name
                FROM items i
                LEFT JOIN categories c ON i.category_id = c.id
                WHERE i.quantity_on_hand <= i.reorder_level
                ORDER BY i.name ASC";

        $stmt = $this->db->query($sql);
        return $stmt->fetchAll();
    }

    public function getActiveToolLoanCount(): int
    {
        $sql = "SELECT COUNT(*) FROM tool_loans WHERE status = 'LOANED'";
        return (int) $this->db->query($sql)->fetchColumn();
    }
}
