<?php

class Report
{
    private PDO $db;

    public function __construct(PDO $db)
    {
        $this->db = $db;
    }

    private function rangeWhere(array $filters, string $column = 'created_at'): array
    {
        $where  = [];
        $params = [];
        if (!empty($filters['date_from'])) {
            $where[] = "$column >= :date_from";
            $params[':date_from'] = $filters['date_from'] . ' 00:00:00';
        }
        if (!empty($filters['date_to'])) {
            $where[] = "$column <= :date_to";
            $params[':date_to'] = $filters['date_to'] . ' 23:59:59';
        }

        return [$where, $params];
    }

    private function rangeSql(string $sql, string $column, array $filters, string $prefix = ''): array
    {
        [$where, $params] = $this->rangeWhere($filters, $column);
        $stmt = $this->db->prepare(
            $sql . ($where ? ' WHERE ' . implode(' AND ', $where) : '') . $prefix
        );
        $stmt->execute($params);

        return $stmt->fetchAll();
    }

    public function getKpis(array $filters): array
    {
        $statusRow = $this->db->query(
            "SELECT COUNT(*) AS total,
                    SUM(status = 'LOW STOCK') AS low_stock,
                    SUM(status = 'OUT OF STOCK') AS out_of_stock
             FROM consumables"
        )->fetch();

        $where  = [];
        $params = [];
        if (!empty($filters['date_from'])) {
            $where[] = 'created_at >= :date_from';
            $params[':date_from'] = $filters['date_from'] . ' 00:00:00';
        }
        if (!empty($filters['date_to'])) {
            $where[] = 'created_at <= :date_to';
            $params[':date_to'] = $filters['date_to'] . ' 23:59:59';
        }
        $whereSql = $where ? ' WHERE ' . implode(' AND ', $where) : '';

        $in = $this->db->prepare('SELECT COALESCE(SUM(quantity), 0) FROM stock_requests' . $whereSql);
        $in->execute($params);

        $out = $this->db->prepare('SELECT COALESCE(SUM(quantity), 0) FROM withdrawals' . $whereSql);
        $out->execute($params);

        return [
            'total_items'   => (int) ($statusRow['total'] ?? 0),
            'low_stock'     => (int) ($statusRow['low_stock'] ?? 0),
            'out_of_stock'  => (int) ($statusRow['out_of_stock'] ?? 0),
            'stock_in_qty'  => (int) $in->fetchColumn(),
            'stock_out_qty' => (int) $out->fetchColumn(),
        ];
    }

    /* ===== Trend aggregation ==================================== */

    private function trendSql(string $table, string $interval, array $filters): array
    {
        switch ($interval) {
            case 'daily':
                $group = "DATE($table.created_at)";
                $label = "DATE_FORMAT(MIN($table.created_at), '%b %e')";
                $order = 'day_key';
                break;
            case 'weekly':
                $group = "YEARWEEK($table.created_at, 3)";
                $label = "DATE_FORMAT(MIN($table.created_at), '%b %e')";
                $order = 'week_key';
                break;
            default: // monthly
                $group = "DATE_FORMAT($table.created_at, '%Y-%m')";
                $label = "DATE_FORMAT(MIN($table.created_at), '%Y-%m')";
                $order = 'month_key';
                break;
        }

        [$where, $params] = $this->rangeWhere($filters, "$table.created_at");

        $sql = "SELECT $group AS period, $label AS label,
                       COUNT(*) AS txn_count,
                       SUM(quantity) AS total_qty,
                       MIN($table.created_at) AS sort_key
                FROM $table"
                . ($where ? ' WHERE ' . implode(' AND ', $where) : '') .
                " GROUP BY $group
                  ORDER BY sort_key ASC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetchAll();
    }

    public function getInTrend(string $interval, array $filters): array
    {
        return $this->trendSql('stock_requests', $interval, $filters);
    }

    public function getOutTrend(string $interval, array $filters): array
    {
        return $this->trendSql('withdrawals', $interval, $filters);
    }

    /* ===== Stock-In analytics ================================ */

    public function getStockInByDay(array $filters): array
    {
        $sql = "SELECT DATE(sr.created_at) AS day,
                       COUNT(*) AS request_count,
                       SUM(sr.quantity) AS total_qty,
                       SUM(sr.total_price) AS total_value
                FROM stock_requests sr";

        return $this->rangeSql($sql, 'sr.created_at', $filters, ' ORDER BY day DESC');
    }

    public function getStockInTopItems(array $filters): array
    {
        $sql = "SELECT c.item_code, c.item_name,
                       SUM(sr.quantity) AS total_qty,
                       COUNT(*) AS request_count,
                       SUM(sr.status = 'RECEIVED') AS received_count,
                       SUM(sr.total_price) AS total_value
                FROM stock_requests sr
                JOIN consumables c ON c.id = sr.consumable_id";

        return $this->rangeSql($sql, 'sr.created_at', $filters, ' GROUP BY c.id ORDER BY total_qty DESC LIMIT 15');
    }

    public function getStockInFulfillment(array $filters): array
    {
        [$where, $params] = $this->rangeWhere($filters, 'sr.created_at');
        $sql = "SELECT sr.status,
                       COUNT(*) AS request_count,
                       SUM(sr.quantity) AS total_qty,
                       SUM(sr.total_price) AS total_value
                FROM stock_requests sr"
                . ($where ? ' WHERE ' . implode(' AND ', $where) : '') .
                ' GROUP BY status
                  ORDER BY request_count DESC';

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetchAll();
    }

    /* ===== Stock-Out analytics =============================== */

    public function getStockOutTopItems(array $filters): array
    {
        $sql = "SELECT c.item_code, c.item_name, c.uom,
                       SUM(w.quantity) AS total_qty,
                       COUNT(*) AS withdrawal_count,
                       COUNT(DISTINCT w.machine_equipment) AS machines_served
                FROM withdrawals w
                JOIN consumables c ON c.id = w.consumable_id";

        return $this->rangeSql($sql, 'w.created_at', $filters, ' GROUP BY c.id ORDER BY total_qty DESC LIMIT 15');
    }

    public function getStockOutByMachine(array $filters): array
    {
        $sql = "SELECT COALESCE(NULLIF(w.machine_equipment, ''), 'Unassigned') AS machine,
                       SUM(w.quantity) AS total_qty,
                       COUNT(*) AS withdrawal_count,
                       COUNT(DISTINCT w.consumable_id) AS distinct_items,
                       MAX(w.created_at) AS last_used
                FROM withdrawals w";

        return $this->rangeSql($sql, 'w.created_at', $filters, " GROUP BY machine ORDER BY total_qty DESC LIMIT 15");
    }

    public function getStockOutByDay(array $filters): array
    {
        $sql = "SELECT DATE(w.created_at) AS day,
                       COUNT(*) AS withdrawal_count,
                       SUM(w.quantity) AS total_qty
                FROM withdrawals w";

        return $this->rangeSql($sql, 'w.created_at', $filters, ' ORDER BY day DESC');
    }
}
