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
                (description, requested_by, department, location, plant, severity,
                 equipment_name, machine_tag, picture_path)
             VALUES
                (:description, :requested_by, :department, :location, :plant, :severity,
                 :equipment_name, :machine_tag, :picture_path)"
        );
        return $stmt->execute([
            ':description'     => $data['description'],
            ':requested_by'    => $data['requested_by'],
            ':department'      => $data['department'] !== '' ? $data['department'] : null,
            ':location'        => $data['location'] !== '' ? $data['location'] : null,
            ':plant'           => $data['plant'] !== '' ? $data['plant'] : null,
            ':severity'        => $data['severity'] !== '' ? $data['severity'] : null,
            ':equipment_name'  => $data['equipment_name'] !== '' ? $data['equipment_name'] : null,
            ':machine_tag'     => $data['machine_tag'] !== '' ? $data['machine_tag'] : null,
            ':picture_path'    => $data['picture_path'] !== '' ? $data['picture_path'] : null,
        ]);
    }

    public function assign(int $id, string $technician): bool
    {
        $stmt = $this->db->prepare(
            "UPDATE service_requests
             SET technician = :technician, status = 'ON GOING', assigned_at = NOW()
             WHERE id = :id AND status = 'REQUEST'"
        );
        $stmt->execute([':technician' => $technician, ':id' => $id]);
        return $stmt->rowCount() > 0;
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
