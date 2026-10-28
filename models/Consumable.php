<?php

class Consumable
{
    private PDO $db;

    public function __construct(PDO $db)
    {
        $this->db = $db;
    }

    public static function calculateStatus(int $actualStockQty, int $lowLevelQty): string
    {
        if ($actualStockQty <= 0) {
            return 'OUT OF STOCK';
        }

        if ($actualStockQty <= $lowLevelQty) {
            return 'LOW STOCK';
        }

        return 'IN STOCK';
    }

    public function getAll(): array
    {
        $sql = "SELECT * FROM consumables ORDER BY item_code ASC";
        return $this->db->query($sql)->fetchAll();
    }

    public function getPaginated(int $limit, int $offset): array
    {
        $stmt = $this->db->prepare(
            'SELECT * FROM consumables ORDER BY item_code ASC LIMIT :limit OFFSET :offset'
        );
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function getCount(): int
    {
        return (int) $this->db->query('SELECT COUNT(*) FROM consumables')->fetchColumn();
    }

    private function buildFilter(string $search, string $supplier, string $status, array &$params): string
    {
        $where = [];
        if ($search !== '') {
            $where[] = "(CONCAT_WS(' ', item_code, item_name, machine_equipment, identification) LIKE :search)";
            $params[':search'] = '%' . $search . '%';
        }
        if ($supplier !== '') {
            $where[] = 'supplier = :supplier';
            $params[':supplier'] = $supplier;
        }
        if ($status !== '') {
            $where[] = 'status = :status';
            $params[':status'] = $status;
        }
        return $where ? ' WHERE ' . implode(' AND ', $where) : '';
    }

    public function getFiltered(int $limit, int $offset, string $search = '', string $supplier = '', string $status = ''): array
    {
        $params = [];
        $where = $this->buildFilter($search, $supplier, $status, $params);
        $sql = "SELECT * FROM consumables{$where} ORDER BY item_code ASC LIMIT :limit OFFSET :offset";
        $stmt = $this->db->prepare($sql);
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function getFilteredCount(string $search = '', string $supplier = '', string $status = ''): int
    {
        $params = [];
        $where = $this->buildFilter($search, $supplier, $status, $params);
        $stmt = $this->db->prepare("SELECT COUNT(*) FROM consumables{$where}");
        $stmt->execute($params);
        return (int) $stmt->fetchColumn();
    }

    public function getSuppliers(): array
    {
        $rows = $this->db->query(
            "SELECT DISTINCT supplier FROM consumables
             WHERE supplier IS NOT NULL AND supplier <> ''
             ORDER BY supplier ASC"
        )->fetchAll(PDO::FETCH_COLUMN);
        return $rows;
    }

    public function getStatusCounts(): array
    {
        $sql = "SELECT
                    COUNT(*) AS total,
                    SUM(status = 'IN STOCK') AS in_stock,
                    SUM(status = 'LOW STOCK') AS low_stock,
                    SUM(status = 'OUT OF STOCK') AS out_of_stock
                FROM consumables";

        $row = $this->db->query($sql)->fetch();

        return [
            'total'        => (int) ($row['total'] ?? 0),
            'in_stock'     => (int) ($row['in_stock'] ?? 0),
            'low_stock'    => (int) ($row['low_stock'] ?? 0),
            'out_of_stock' => (int) ($row['out_of_stock'] ?? 0),
        ];
    }

    public function getById(int $id): ?array
    {
        $stmt = $this->db->prepare('SELECT * FROM consumables WHERE id = :id');
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch();
        return $row !== false ? $row : null;
    }

    public function add(array $data): bool
    {
        $status = self::calculateStatus(
            (int) $data['actual_stock_qty'],
            (int) $data['low_level_qty']
        );

        $sql = "INSERT INTO consumables
                    (item_code, item_name, description, supplier, recommended_qty,
                     low_level_qty, actual_stock_qty, request_qty, uom,
                     machine_equipment, identification, price_per_piece, status)
                VALUES
                    (:item_code, :item_name, :description, :supplier, :recommended_qty,
                     :low_level_qty, :actual_stock_qty, :request_qty, :uom,
                     :machine_equipment, :identification, :price_per_piece, :status)";

        $stmt = $this->db->prepare($sql);

        try {
            return $stmt->execute([
                ':item_code'         => $data['item_code'],
                ':item_name'         => $data['item_name'],
                ':description'       => $data['description'] ?: null,
                ':supplier'          => $data['supplier'] ?: null,
                ':recommended_qty'   => (int) $data['recommended_qty'],
                ':low_level_qty'     => (int) $data['low_level_qty'],
                ':actual_stock_qty'  => (int) $data['actual_stock_qty'],
                ':request_qty'       => (int) $data['request_qty'],
                ':uom'               => $data['uom'],
                ':machine_equipment' => $data['machine_equipment'] ?: null,
                ':identification'    => $data['identification'] ?: null,
                ':price_per_piece'   => (float) $data['price_per_piece'],
                ':status'            => $status,
            ]);
        } catch (PDOException $e) {
            if ($e->getCode() === '23000') {
                throw new RuntimeException('Item Code already exists.');
            }
            throw $e;
        }
    }

    public function update(array $data): bool
    {
        $status = self::calculateStatus(
            (int) $data['actual_stock_qty'],
            (int) $data['low_level_qty']
        );

        $sql = "UPDATE consumables SET
                    item_code         = :item_code,
                    item_name         = :item_name,
                    description       = :description,
                    supplier          = :supplier,
                    recommended_qty   = :recommended_qty,
                    low_level_qty     = :low_level_qty,
                    actual_stock_qty  = :actual_stock_qty,
                    request_qty       = :request_qty,
                    uom               = :uom,
                    machine_equipment = :machine_equipment,
                    identification    = :identification,
                    price_per_piece   = :price_per_piece,
                    status            = :status
                WHERE id = :id";

        $stmt = $this->db->prepare($sql);

        try {
            return $stmt->execute([
                ':item_code'         => $data['item_code'],
                ':item_name'         => $data['item_name'],
                ':description'       => $data['description'] ?: null,
                ':supplier'          => $data['supplier'] ?: null,
                ':recommended_qty'   => (int) $data['recommended_qty'],
                ':low_level_qty'     => (int) $data['low_level_qty'],
                ':actual_stock_qty'  => (int) $data['actual_stock_qty'],
                ':request_qty'       => (int) $data['request_qty'],
                ':uom'               => $data['uom'],
            'machine_equipment' => $data['machine_equipment'] ?: null,
            ':identification'   => $data['identification'] ?: null,
            ':price_per_piece'   => (float) $data['price_per_piece'],
            ':status'            => $status,
            ':id'                => (int) $data['id'],
            ]);
        } catch (PDOException $e) {
            if ($e->getCode() === '23000') {
                throw new RuntimeException('Item Code already exists on another item.');
            }
            throw $e;
        }
    }

    public function bulkInsert(array $rows): array
    {
        $inserted = 0;
        $skipped = [];

        $stmt = $this->db->prepare(
            "INSERT INTO consumables
                (item_code, item_name, description, supplier, uom, identification,
                 recommended_qty, low_level_qty, actual_stock_qty, status)
             VALUES
                (:item_code, :item_name, :description, :supplier, :uom, :identification,
                 :recommended_qty, :low_level_qty, 0, :status)"
        );

        foreach ($rows as $row) {
            try {
                $stmt->execute([
                    ':item_code'       => $row['item_code'],
                    ':item_name'       => $row['item_name'],
                    ':description'     => $row['description'] ?: null,
                    ':supplier'        => $row['supplier'] ?: null,
                    ':uom'             => $row['uom'] ?: 'pcs',
                    ':identification'  => $row['identification'] ?: null,
                    ':recommended_qty' => (int) ($row['recommended_qty'] ?? 0),
                    ':low_level_qty'   => (int) ($row['low_level_qty'] ?? 0),
                    ':status'          => self::calculateStatus(0, (int) ($row['low_level_qty'] ?? 0)),
                ]);
                $inserted++;
            } catch (PDOException $e) {
                if ($e->getCode() === '23000') {
                    $skipped[] = $row['item_code'];
                    continue;
                }
                throw $e;
            }
        }

        return ['inserted' => $inserted, 'skipped' => $skipped];
    }

    public function delete(int $id): bool
    {
        $stmt = $this->db->prepare('DELETE FROM consumables WHERE id = :id');
        return $stmt->execute([':id' => $id]);
    }

    public function updateActualStock(int $id, int $newActualQty): bool
    {
        $item = $this->getById($id);
        if ($item === null) {
            return false;
        }

        $status = self::calculateStatus($newActualQty, (int) $item['low_level_qty']);

        $stmt = $this->db->prepare(
            "UPDATE consumables
             SET actual_stock_qty = :qty, status = :status
             WHERE id = :id"
        );

        return $stmt->execute([
            ':qty'    => $newActualQty,
            ':status' => $status,
            ':id'     => $id,
        ]);
    }

    public function updateRequestQty(int $id, int $newRequestQty): bool
    {
        $stmt = $this->db->prepare(
            'UPDATE consumables SET request_qty = :req WHERE id = :id'
        );

        return $stmt->execute([':req' => $newRequestQty, ':id' => $id]);
    }
}
