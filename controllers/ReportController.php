<?php

require_once __DIR__ . '/../config/Database.php';
require_once __DIR__ . '/../models/Report.php';

class ReportController
{
    private Report $report;

    private const REPORTS = ['inventory', 'stock_in', 'stock_out'];

    public function __construct()
    {
        $database = new Database();
        $this->report = new Report($database->getConnection());
    }

    private function filters(): array
    {
        $from = $_GET['date_from'] ?? '';
        $to   = $_GET['date_to'] ?? '';

        $from = preg_match('/^\d{4}-\d{2}-\d{2}$/', $from) ? $from : '';
        $to   = preg_match('/^\d{4}-\d{2}-\d{2}$/', $to) ? $to : '';

        return ['date_from' => $from, 'date_to' => $to];
    }

    private function reportType(): string
    {
        $type = $_GET['report'] ?? 'inventory';

        return in_array($type, self::REPORTS, true) ? $type : 'inventory';
    }

    private function interval(): string
    {
        $interval = $_GET['interval'] ?? 'monthly';

        return in_array($interval, ['monthly', 'weekly', 'daily'], true) ? $interval : 'monthly';
    }

    private function analytics(string $type, array $filters): array
    {
        switch ($type) {
            case 'stock_in':
                return [
                    ['title' => 'Stock-In Volume by Day',
                     'headers' => ['Date', 'Requests', 'Total Qty', 'Total Value (P)'],
                     'rows' => $this->report->getStockInByDay($filters)],
                    ['title' => 'Top Requested Items',
                     'headers' => ['Item Code', 'Item', 'Total Qty', 'Requests', 'Received', 'Total Value (P)'],
                     'rows' => $this->report->getStockInTopItems($filters)],
                    ['title' => 'Fulfillment Status Breakdown',
                     'headers' => ['Status', 'Requests', 'Total Qty', 'Total Value (P)'],
                     'rows' => $this->report->getStockInFulfillment($filters)],
                ];

            case 'stock_out':
                return [
                    ['title' => 'Top Consumed Items',
                     'headers' => ['Item Code', 'Item', 'UOM', 'Total Qty Consumed', 'Withdrawals', 'Machines Served'],
                     'rows' => $this->report->getStockOutTopItems($filters)],
                    ['title' => 'Equipment Consumption Breakdown',
                     'headers' => ['Machine / Equipment', 'Total Qty', 'Withdrawals', 'Distinct Items', 'Last Withdrawal'],
                     'rows' => $this->report->getStockOutByMachine($filters)],
                    ['title' => 'Consumption Volume by Day',
                     'headers' => ['Date', 'Withdrawals', 'Total Qty'],
                     'rows' => $this->report->getStockOutByDay($filters)],
                ];

            default:
                return [];
        }
    }

    public function index(): void
    {
        $type     = $this->reportType();
        $filters  = $this->filters();
        $interval = $this->interval();

        $kpis    = $this->report->getKpis($filters);
        $titles  = ['inventory' => 'Inventory Valuation & Stock Status Summary',
                    'stock_in'  => 'Stock-In Analytics',
                    'stock_out' => 'Stock-Out (Consumption & Machine Usage) Analytics'];
        $activeReport  = $titles[$type];
        $sections      = $this->analytics($type, $filters);
        $dateSensitive = $type !== 'inventory';

        // Trend datasets for the chart (both stock-in and stock-out on one timeline)
        $inRows  = $this->report->getInTrend($interval, $filters);
        $outRows = $this->report->getOutTrend($interval, $filters);

        $labels = [];
        foreach ($inRows as $row) {
            $labels[$row['period']] = $row['label'];
        }
        foreach ($outRows as $row) {
            $labels[$row['period']] = $row['label'];
        }
        ksort($labels);

        $trendLabels = array_values($labels);
        $trendIn     = [];
        $trendOut    = [];
        $trendInQty  = [];
        $trendOutQty = [];
        foreach ($labels as $period => $label) {
            $trendIn[$period] = $trendInQty[$period] = 0;
            $trendOut[$period] = $trendOutQty[$period] = 0;
        }
        foreach ($inRows as $row) {
            $trendIn[$row['period']]  = (int) $row['txn_count'];
            $trendInQty[$row['period']] = (int) $row['total_qty'];
        }
        foreach ($outRows as $row) {
            $trendOut[$row['period']]  = (int) $row['txn_count'];
            $trendOutQty[$row['period']] = (int) $row['total_qty'];
        }

        require __DIR__ . '/../views/reports/index.php';
    }

    public function exportCsv(): void
    {
        $type    = $this->reportType();
        $filters = $this->filters();
        $titles  = ['inventory' => 'INVENTORY VALUATION & STOCK STATUS SUMMARY',
                    'stock_in'  => 'STOCK-IN ANALYTICS',
                    'stock_out' => 'STOCK-OUT (CONSUMPTION & MACHINE USAGE) ANALYTICS'];
        $sections = $this->analytics($type, $filters);

        $range = ($filters['date_from'] || $filters['date_to'])
            ? '_' . ($filters['date_from'] ?: 'start') . '_to_' . ($filters['date_to'] ?: 'now')
            : '_all';

        // Discard any output already buffered by the HTML layout
        while (ob_get_level() > 0) {
            ob_end_clean();
        }

        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename=' . strtolower($type) . '_report' . $range . '.csv');

        $out = fopen('php://output', 'w');

        fputcsv($out, [$titles[$type]]);
        if ($filters['date_from'] || $filters['date_to']) {
            fputcsv($out, ['Filter:', 'From', $filters['date_from'] ?: '(start)', 'To', $filters['date_to'] ?: '(now)']);
        }
        fputcsv($out, []);

        foreach ($sections as $section) {
            fputcsv($out, [$section['title']]);
            fputcsv($out, $section['headers']);
            foreach ($section['rows'] as $row) {
                $outRow = [];
                foreach ($row as $i => $value) {
                $header = $section['headers'][$i] ?? '';
                if (str_ends_with($header, '(P)') && $value !== null && $value !== '') {
                    $value = number_format((float) $value, 2);
                }
                    $outRow[] = $value;
                }
                fputcsv($out, $outRow);
            }
            fputcsv($out, []);
        }

        fclose($out);
        exit;
    }
}
