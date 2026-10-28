<?php

require_once __DIR__ . '/../config/Database.php';
require_once __DIR__ . '/../models/ServiceRequest.php';

class ServiceRequestController
{
    private ServiceRequest $serviceRequest;

    public function __construct()
    {
        $database = new Database();
        $this->serviceRequest = new ServiceRequest($database->getConnection());
    }

    private const MANAGE_ROLES = ['admin', 'user', 'maintenance_technician'];
    private const REQUESTOR_ROLES = ['cianan_user', 'production_operator'];

    private function isScopedUser(): bool
    {
        return in_array($_SESSION['role'] ?? '', self::REQUESTOR_ROLES, true);
    }

    public function dashboard(): void
    {
        $all = $this->serviceRequest->getAll();

        // next SR number comes from the whole table, not just the user's slice
        $nextSrNo = count($all) > 0
            ? ((int) max(array_map(fn ($r) => (int) $r['id'], $all)) + 1)
            : 1;

        if ($this->isScopedUser()) {
            $me = $_SESSION['full_name'] ?? '';
            $all = array_values(array_filter($all, fn ($r) => $r['requested_by'] === $me));
        }

        $columns = [
            'REQUEST'   => ['title' => 'Request',   'cards' => []],
            'ON GOING'  => ['title' => 'On Going',  'cards' => []],
            'COMPLETED' => ['title' => 'Completed', 'cards' => []],
        ];
        foreach ($all as $r) {
            if (isset($columns[$r['status']])) {
                $columns[$r['status']]['cards'][] = $r;
            }
        }

        // Keep the board light: only the recent completions stay on the kanban,
        // the rest lives in the Completed Service Requests sub-tab
        $columns['COMPLETED']['cards'] = array_slice($columns['COMPLETED']['cards'], 0, 10);

        $ids = array_map(fn ($r) => (int) $r['id'], $all);
        $assignMap = $this->serviceRequest->getAssignmentsMap($ids);

        $scoped = $this->isScopedUser();
        $emptyMessage = "You have not created any service request yet. Use &ldquo;+ New Service Request&rdquo; above to file your first one.";
        $completedTotal = $scoped
            ? $this->serviceRequest->getCompletedCount($_SESSION['full_name'] ?? '')
            : $this->serviceRequest->getCompletedCount();

        $technicians = $this->serviceRequest->getTechnicians();

        require __DIR__ . '/../views/service_request/index.php';
    }

    public function completed(): void
    {
        $perPage = (int) ($_GET['per_page'] ?? 10);
        if (!in_array($perPage, [10, 25, 50, 100], true)) {
            $perPage = 10;
        }
        $pg = max(1, (int) ($_GET['pg'] ?? 1));

        $scoped = $this->isScopedUser();
        $me = $scoped ? ($_SESSION['full_name'] ?? '') : '';

        $total = $this->serviceRequest->getCompletedCount($me);
        $totalPages = max(1, (int) ceil($total / $perPage));
        if ($pg > $totalPages) {
            $pg = $totalPages;
        }

        $completed = $this->serviceRequest->getCompletedPaginated($perPage, ($pg - 1) * $perPage, $me);
        $ids = array_map(fn ($r) => (int) $r['id'], $completed);
        $assignMap = $this->serviceRequest->getAssignmentsMap($ids);

        $pagination = [
            'pg'         => $pg,
            'perPage'    => $perPage,
            'total'      => $total,
            'totalPages' => $totalPages,
        ];

        require __DIR__ . '/../views/service_request/completed.php';
    }

    public function handleCreate(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: /CIANANMRO/public/index.php?page=service_request');
            exit;
        }

        $description = trim($_POST['description'] ?? '');
        $requestedBy = trim($_POST['requested_by'] ?? '');
        $department = trim($_POST['department'] ?? '');

        if ($description !== '' && $requestedBy !== '') {
            // OTHERS: store the typed department so it shows in the records
            if ($department === 'OTHERS') {
                $department = trim($_POST['department_custom'] ?? '');
            }

            $data = [
                'description'     => $description,
                'requested_by'    => $requestedBy,
                'department'      => $department,
                'location'        => trim($_POST['location'] ?? ''),
                'plant'           => strtoupper(trim($_POST['plant'] ?? '')),
                'severity'        => strtoupper(trim($_POST['severity'] ?? '')),
                'equipment_name'  => trim($_POST['equipment_name'] ?? ''),
                'machine_tag'     => trim($_POST['machine_tag'] ?? ''),
                'picture_path'    => '',
            ];

            if (in_array($data['plant'], ['SPRING BOOK', 'WILLYDRIVE'], true) === false) {
                $data['plant'] = '';
            }
            if (in_array($data['severity'], ['CRITICAL', 'HIGH', 'MODERATE', 'LOW'], true) === false) {
                $data['severity'] = '';
            }

            // Nature of request: MACHINE REPAIR straight, OTHERS stored as typed
            $nature = strtoupper(trim($_POST['nature_of_request'] ?? ''));
            if ($nature === 'OTHERS') {
                $nature = trim($_POST['nature_custom'] ?? '');
            }
            $data['nature_of_request'] = $nature;

            $picture = $this->storePicture();
            if ($picture !== '' && $picture !== null) {
                $data['picture_path'] = $picture;
            }

            try {
                $this->serviceRequest->create($data);
            } catch (PDOException $e) {
                header('Location: /CIANANMRO/public/index.php?page=service_request&status=create_error');
                exit;
            }
        }

        header('Location: /CIANANMRO/public/index.php?page=service_request&status=created');
        exit;
    }

    private function storePicture(): string
    {
        $file = $_FILES['picture'] ?? null;

        if ($file === null || $file['error'] === UPLOAD_ERR_NO_FILE) {
            return '';
        }

        if ($file['error'] !== UPLOAD_ERR_OK) {
            return '';
        }

        if ($file['size'] > 5 * 1024 * 1024) {
            return '';
        }

        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if (!in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp'], true)) {
            return '';
        }

        $dir = __DIR__ . '/../public/uploads/service_requests';
        if (!is_dir($dir)) {
            mkdir($dir, 0777, true);
        }

        $filename = 'sr_' . date('Ymd_His') . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
        $dest = $dir . '/' . $filename;

        if (!move_uploaded_file($file['tmp_name'], $dest)) {
            return '';
        }

        return 'public/uploads/service_requests/' . $filename;
    }

    public function handleAssign(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: /CIANANMRO/public/index.php?page=service_request');
            exit;
        }

        $role = $_SESSION['role'] ?? '';
        if (!in_array($role, self::MANAGE_ROLES, true)) {
            header('Location: /CIANANMRO/public/index.php?page=service_request&status=sr_forbidden');
            exit;
        }

        $id = (int) ($_POST['id'] ?? 0);
        $technicians = (array) ($_POST['technicians'] ?? []);
        $shift = trim($_POST['shift'] ?? '');

        if (!in_array($shift, ['6AM-2PM', '2PM-10PM', '10PM-6AM'], true)) {
            $shift = '';
        }

        // Filter to names that are actually registered maintenance technicians
        $validNames = array_column($this->serviceRequest->getTechnicians(), 'full_name');
        $technicians = array_values(array_intersect($technicians, $validNames));

        if ($id > 0 && !empty($technicians) && $shift !== '') {
            $assigned = $this->serviceRequest->assign($id, $technicians, $shift);
            $status = $assigned ? 'assigned' : 'assign_error';
        } else {
            $status = 'assign_error';
        }

        header('Location: /CIANANMRO/public/index.php?page=service_request&status=' . $status);
        exit;
    }

    public function handleComplete(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: /CIANANMRO/public/index.php?page=service_request');
            exit;
        }

        $role = $_SESSION['role'] ?? '';
        if (!in_array($role, self::MANAGE_ROLES, true)) {
            header('Location: /CIANANMRO/public/index.php?page=service_request&status=sr_forbidden');
            exit;
        }

        $id = (int) ($_POST['id'] ?? 0);

        if ($id > 0) {
            $this->serviceRequest->complete($id);
        }

        header('Location: /CIANANMRO/public/index.php?page=service_request&status=completed');
        exit;
    }
}
