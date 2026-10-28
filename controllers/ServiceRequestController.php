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

    public function dashboard(): void
    {
        $requests = $this->serviceRequest->getAll();

        $columns = [
            'REQUEST'   => ['title' => 'Request',   'cards' => []],
            'ON GOING'  => ['title' => 'On Going',  'cards' => []],
            'COMPLETED' => ['title' => 'Completed', 'cards' => []],
        ];
        foreach ($requests as $r) {
            if (isset($columns[$r['status']])) {
                $columns[$r['status']]['cards'][] = $r;
            }
        }

        $technicians = $this->serviceRequest->getTechnicians();
        $nextSrNo = count($requests) > 0
            ? ((int) max(array_map(fn ($r) => (int) $r['id'], $requests)) + 1)
            : 1;

        require __DIR__ . '/../views/service_request/index.php';
    }

    public function handleCreate(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: /CIANANMRO/public/index.php?page=service_request');
            exit;
        }

        $description = trim($_POST['description'] ?? '');
        $requestedBy = trim($_POST['requested_by'] ?? '');

        if ($description !== '' && $requestedBy !== '') {
            $data = [
                'description'     => $description,
                'requested_by'    => $requestedBy,
                'department'      => trim($_POST['department'] ?? ''),
                'location'        => trim($_POST['location'] ?? ''),
                'plant'           => strtoupper(trim($_POST['plant'] ?? '')),
                'equipment_name'  => trim($_POST['equipment_name'] ?? ''),
                'machine_tag'     => trim($_POST['machine_tag'] ?? ''),
                'picture_path'    => '',
            ];

            if (in_array($data['plant'], ['SPRING BOOK', 'WILLYDRIVE'], true) === false) {
                $data['plant'] = '';
            }

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

        $id = (int) ($_POST['id'] ?? 0);
        $technician = trim($_POST['technician'] ?? '');

        if ($id > 0 && $technician !== '') {
            $this->serviceRequest->assign($id, $technician);
        }

        header('Location: /CIANANMRO/public/index.php?page=service_request&status=assigned');
        exit;
    }

    public function handleComplete(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: /CIANANMRO/public/index.php?page=service_request');
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
