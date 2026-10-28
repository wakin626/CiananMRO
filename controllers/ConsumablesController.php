<?php

require_once __DIR__ . '/../config/Database.php';
require_once __DIR__ . '/../models/Consumable.php';

class ConsumablesController
{
    private Consumable $consumable;

    public function __construct()
    {
        $database = new Database();
        $this->consumable = new Consumable($database->getConnection());
    }

    public function index(): void
    {
        $perPage = (int) ($_GET['per_page'] ?? 25);
        if (!in_array($perPage, [10, 25, 50, 100], true)) {
            $perPage = 25;
        }

        $pg = max(1, (int) ($_GET['pg'] ?? 1));

        $search = trim((string) ($_GET['q'] ?? ''));
        $supplier = trim((string) ($_GET['supplier'] ?? ''));
        $status = trim((string) ($_GET['status_filter'] ?? ''));
        if (!in_array($status, ['IN STOCK', 'LOW STOCK', 'OUT OF STOCK'], true)) {
            $status = '';
        }

        $total = $this->consumable->getFilteredCount($search, $supplier, $status);
        $totalPages = max(1, (int) ceil($total / $perPage));
        if ($pg > $totalPages) {
            $pg = $totalPages;
        }

        $consumables = $this->consumable->getFiltered($perPage, ($pg - 1) * $perPage, $search, $supplier, $status);
        $suppliers = $this->consumable->getSuppliers();

        $filters = [
            'search'   => $search,
            'supplier' => $supplier,
            'status'   => $status,
        ];

        $pagination = [
            'pg'         => $pg,
            'perPage'    => $perPage,
            'total'      => $total,
            'totalPages' => $totalPages,
        ];

        require __DIR__ . '/../views/consumables/index.php';
    }

    private function buildItemData(array $post, int $id = 0): array
    {
        $description = trim($post['description'] ?? '');

        return [
            'id'                => $id,
            'item_code'         => strtoupper(trim($post['item_code'] ?? '')),
            'item_name'         => $description,
            'description'       => '',
            'supplier'          => trim($post['supplier'] ?? ''),
            'recommended_qty'   => (int) ($_POST['recommended_qty'] ?? 0),
            'low_level_qty'     => (int) ($_POST['low_level_qty'] ?? 0),
            'actual_stock_qty'  => (int) ($_POST['actual_stock_qty'] ?? 0),
            'request_qty'       => (int) ($_POST['request_qty'] ?? 0),
            'uom'               => trim($_POST['uom'] ?? 'pcs'),
            'machine_equipment' => trim($_POST['machine_equipment'] ?? ''),
            'identification'    => trim($_POST['identification'] ?? ''),
            'price_per_piece'   => (float) ($_POST['price_per_piece'] ?? 0),
        ];
    }

    public function handleAdd(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: /CIANANMRO/public/index.php?page=consumables');
            exit;
        }

        $data = $this->buildItemData($_POST);

        $error = null;

        if ($data['item_code'] !== '') {
            try {
                $this->consumable->add($data);
                header('Location: /CIANANMRO/public/index.php?page=consumables&status=added');
                exit;
            } catch (RuntimeException $e) {
                $error = $e->getMessage();
            } catch (PDOException $e) {
                $error = 'Database error: ' . $e->getMessage();
            }
        } else {
            $error = 'Item Code is needed to save (it must be unique).';
        }

        $consumables = $this->consumable->getAll();
        require_once __DIR__ . '/../views/layout/header.php';
        require __DIR__ . '/../views/consumables/index.php';
        require __DIR__ . '/../views/layout/footer.php';
    }

    public function handleUpdate(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: /CIANANMRO/public/index.php?page=consumables');
            exit;
        }

        $data = $this->buildItemData($_POST, (int) ($_POST['id'] ?? 0));

        $error = null;

        if ($data['id'] <= 0) {
            $error = 'Invalid item.';
        } else {
            try {
                $this->consumable->update($data);
                header('Location: /CIANANMRO/public/index.php?page=consumables&status=updated');
                exit;
            } catch (RuntimeException $e) {
                $error = $e->getMessage();
            } catch (PDOException $e) {
                $error = 'Database error: ' . $e->getMessage();
            }
        }

        $consumables = $this->consumable->getAll();
        require_once __DIR__ . '/../views/layout/header.php';
        require __DIR__ . '/../views/consumables/index.php';
        require __DIR__ . '/../views/layout/footer.php';
    }

    public function handleDelete(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: /CIANANMRO/public/index.php?page=consumables');
            exit;
        }

        $id = (int) ($_POST['id'] ?? 0);

        if ($id > 0) {
            $this->consumable->delete($id);
        }

        header('Location: /CIANANMRO/public/index.php?page=consumables&status=deleted');
        exit;
    }

    public function handleBulkUpload(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_FILES['csv_file'])) {
            header('Location: /CIANANMRO/public/index.php?page=consumables');
            exit;
        }

        $file = $_FILES['csv_file'];

        if ($file['error'] !== UPLOAD_ERR_OK) {
            $error = 'File upload failed. Please try again.';
        } elseif (strtolower(pathinfo($file['name'], PATHINFO_EXTENSION)) !== 'csv') {
            $error = 'Please upload a .csv file (in Excel: File > Save As > CSV).';
        } else {
            $rows = [];
            $current = null;
            $handle = fopen($file['tmp_name'], 'r');

            $finalize = function (array $item) use (&$rows): void {
                $lines = array_values(array_filter(
                    array_map('trim', [$item['desc_a'], $item['desc_b']]),
                    'strlen'
                ));

                // The DESCRIPTION column is the item name (e.g. "BATTERY 9V").
                $name = implode(' ', $lines);

                $rows[] = [
                    'item_code'       => $item['item_code'],
                    'item_name'       => $name,
                    'description'     => '',
                    'supplier'        => $item['supplier'],
                    'uom'             => $item['uom'],
                    'identification'  => $item['identification'],
                    'recommended_qty' => $item['recommended_qty'],
                    'low_level_qty'   => $item['low_level_qty'],
                ];
            };

            while (($cells = fgetcsv($handle)) !== false) {
                $cells = array_map(fn($c) => trim((string) $c), $cells);
                if (implode('', $cells) === '') {
                    continue;
                }
                if (strtoupper($cells[0] ?? '') === 'ITEM CODE') {
                    continue;
                }

                $itemCode = strtoupper($cells[0] ?? '');

                if ($itemCode !== '') {
                    // New item: flush the previous one first
                    if ($current !== null) {
                        $finalize($current);
                    }

                    $current = [
                        'item_code'       => $itemCode,
                        'desc_a'          => $cells[1] ?? '',
                        'desc_b'          => '',
                        'supplier'        => $cells[2] ?? '',
                        'uom'             => $cells[3] ?? '',
                        'identification'  => $cells[4] ?? '',
                        'recommended_qty' => (int) ($cells[5] ?? 0),
                        'low_level_qty'   => (int) ($cells[6] ?? 0),
                    ];
                } elseif ($current !== null) {
                    // Continuation row (merged cells export blanks here):
                    // append description lines and fill any missing fields.
                    if (($cells[1] ?? '') !== '') {
                        if ($current['desc_b'] === '') {
                            $current['desc_b'] = $cells[1];
                        } else {
                            $current['desc_b'] .= "\n" . $cells[1];
                        }
                    }
                    foreach (['supplier' => 2, 'uom' => 3, 'identification' => 4] as $field => $idx) {
                        if ($current[$field] === '' && ($cells[$idx] ?? '') !== '') {
                            $current[$field] = $cells[$idx];
                        }
                    }
                    if ($current['recommended_qty'] === 0 && (int) ($cells[5] ?? 0) !== 0) {
                        $current['recommended_qty'] = (int) $cells[5];
                    }
                    if ($current['low_level_qty'] === 0 && (int) ($cells[6] ?? 0) !== 0) {
                        $current['low_level_qty'] = (int) $cells[6];
                    }
                }
            }

            if ($current !== null) {
                $finalize($current);
            }

            fclose($handle);

            if (empty($rows)) {
                $error = 'No rows found in the file.';
            } else {
                try {
                    $result = $this->consumable->bulkInsert($rows);
                    $msg = $result['inserted'] . ' item(s) uploaded.';
                    if (!empty($result['skipped'])) {
                        $msg .= ' Skipped (duplicate Item Code): ' . implode(', ', $result['skipped']);
                    }
                    header('Location: /CIANANMRO/public/index.php?page=consumables&status=uploaded&msg=' . urlencode($msg));
                    exit;
                } catch (PDOException $e) {
                    $error = 'Database error: ' . $e->getMessage();
                }
            }
        }

        $consumables = $this->consumable->getAll();
        require_once __DIR__ . '/../views/layout/header.php';
        require __DIR__ . '/../views/consumables/index.php';
        require __DIR__ . '/../views/layout/footer.php';
    }
}
