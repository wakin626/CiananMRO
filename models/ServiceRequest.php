<?php

class ServiceRequest
{
    private PDO $db;

    public function __construct(PDO $db)
    {
        $this->db = $db;
    }

    public function getAll(): array
    {
        $sql = "SELECT *,
                    CASE
                        WHEN status = 'REQUEST' THEN created_at
                        WHEN status = 'ON GOING' THEN assigned_at
                        ELSE completed_at
                    END AS moved_at
                FROM service_requests
                ORDER BY moved_at DESC";
        return $this->db->query($sql)->fetchAll();
    }

    public function getRecentCompleted(int $limit = 10): array
    {
        $stmt = $this->db->prepare(
            "SELECT * FROM service_requests
             WHERE status = 'COMPLETED'
             ORDER BY completed_at DESC
             LIMIT :limit"
        );
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function getCompletedCount(): int
    {
        return (int) $this->db->query(
            "SELECT COUNT(*) FROM service_requests WHERE status = 'COMPLETED'"
        )->fetchColumn();
    }

    public function getCompletedPaginated(int $limit, int $offset): array
    {
        $stmt = $this->db->prepare(
            "SELECT * FROM service_requests
             WHERE status = 'COMPLETED'
             ORDER BY completed_at DESC, id DESC
             LIMIT :limit OFFSET :offset"
        );
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function getTechnicians(): array
    {
        return $this->db->query(
            "SELECT id, full_name FROM users
             WHERE role = 'maintenance_technician'
             ORDER BY full_name ASC"
        )->fetchAll();
    }

    public function create(array $data): bool
    {
        $stmt = $this->db->prepare(
            "INSERT INTO service_requests
                (description, requested_by, department, location, plant, severity, nature_of_request,
                 equipment_name, machine_tag, picture_path)
             VALUES
                (:description, :requested_by, :department, :location, :plant, :severity, :nature,
                 :equipment_name, :machine_tag, :picture_path)"
        );
        return $stmt->execute([
            ':description'     => $data['description'],
            ':requested_by'    => $data['requested_by'],
            ':department'      => $data['department'] !== '' ? $data['department'] : null,
            ':location'        => $data['location'] !== '' ? $data['location'] : null,
            ':plant'           => $data['plant'] !== '' ? $data['plant'] : null,
            ':severity'        => $data['severity'] !== '' ? $data['severity'] : null,
            ':nature'          => $data['nature_of_request'] !== '' ? $data['nature_of_request'] : null,
            ':equipment_name'  => $data['equipment_name'] !== '' ? $data['equipment_name'] : null,
            ':machine_tag'     => $data['machine_tag'] !== '' ? $data['machine_tag'] : null,
            ':picture_path'    => $data['picture_path'] !== '' ? $data['picture_path'] : null,
        ]);
    }

    public function getAssignmentsMap(array $ids): array
    {
        if (empty($ids)) {
            return [];
        }
        $in = implode(',', array_fill(0, count($ids), '?'));
        $stmt = $this->db->prepare(
            "SELECT service_request_id, technician, shift
             FROM service_request_techs
             WHERE service_request_id IN ($in)
             ORDER BY id ASC"
        );
        $stmt->execute($ids);

        $map = [];
        foreach ($stmt->fetchAll() as $row) {
            $map[$row['service_request_id']][] =
                htmlspecialchars($row['technician'], ENT_QUOTES, 'UTF-8')
                . ' &middot; ' . htmlspecialchars($row['shift'], ENT_QUOTES, 'UTF-8');
        }
        return $map;
    }

    public function assign(int $id, array $technicians, string $shift): bool
    {
        $technicians = array_values(array_filter(array_map('trim', $technicians), 'strlen'));
        if (empty($technicians)) {
            return false;
        }

        $stmt = $this->db->prepare('SELECT status FROM service_requests WHERE id = :id');
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch();
        if ($row === false || $row['status'] !== 'REQUEST') {
            return false;
        }

        $this->db->beginTransaction();
        try {
            $insert = $this->db->prepare(
                "INSERT INTO service_request_techs (service_request_id, technician, shift)
                 VALUES (:sr_id, :technician, :shift)"
            );
            foreach ($technicians as $tech) {
                $insert->execute([
                    ':sr_id'      => $id,
                    ':technician' => $tech,
                    ':shift'      => $shift,
                ]);
            }

            $this->db->prepare(
                "UPDATE service_requests
                 SET technician = :legacy, status = 'ON GOING', assigned_at = NOW()
                 WHERE id = :id"
            )->execute([
                ':legacy' => implode(', ', $technicians),
                ':id'     => $id,
            ]);

            $this->db->commit();
            return true;
        } catch (PDOException $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            return false;
        }
    }

    public function complete(int $id): bool
    {
        $stmt = $this->db->prepare(
            "UPDATE service_requests
             SET status = 'COMPLETED', completed_at = NOW()
             WHERE id = :id AND status = 'ON GOING'"
        );
        $stmt->execute([':id' => $id]);
        return $stmt->rowCount() > 0;
    }

    public function getById(int $id): ?array
    {
        $stmt = $this->db->prepare('SELECT * FROM service_requests WHERE id = :id');
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch();
        return $row !== false ? $row : null;
    }
}
