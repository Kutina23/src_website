<?php
class Scholarship {
    private $db;

    public function __construct($database) {
        $this->db = $database;
    }

    public function getAllActive() {
        return $this->db->fetchAll("SELECT id, title, type, description, amount, eligibility, deadline, external_link, status, created_at, updated_at FROM scholarships WHERE status = ? ORDER BY deadline ASC", ['active']);
    }

    public function getById($id) {
        return $this->db->fetch("SELECT * FROM scholarships WHERE id = ?", [$id]);
    }

    public function getAll(array $filters = []): array {
        $sql = "SELECT id, title, type, description, amount, eligibility, deadline, external_link, status, created_at, updated_at
                FROM scholarships WHERE 1=1";
        $params = [];

        if (!empty($filters['status'])) {
            $sql .= " AND status = ?";
            $params[] = $filters['status'];
        }

        if (!empty($filters['type'])) {
            $sql .= " AND type = ?";
            $params[] = $filters['type'];
        }

        $sql .= " ORDER BY deadline ASC, created_at DESC";
        return $this->db->fetchAll($sql, $params);
    }

    public function getAllTypes(): array {
        return ['Merit-based', 'Need-based', 'Athletic', 'Government', 'International', 'Other'];
    }

    public function create(array $data): int {
        $id = $this->db->insert('scholarships', [
            'title'          => trim($data['title'] ?? ''),
            'type'           => $data['type'] ?? 'Other',
            'description'    => trim($data['description'] ?? ''),
            'amount'         => trim($data['amount'] ?? ''),
            'eligibility'    => trim($data['eligibility'] ?? ''),
            'deadline'       => $data['deadline'] ?? null,
            'external_link'  => trim($data['external_link'] ?? ''),
            'status'         => $data['status'] ?? 'active',
        ]);
        logActivity('create_scholarship', $_SESSION['user_id'] ?? null, [
            'scholarship_id' => $id,
            'title'         => $data['title'] ?? '',
        ]);
        return (int)$id;
    }

    public function update(int $id, array $data): bool {
        $updateData = [
            'title'          => trim($data['title'] ?? ''),
            'type'           => $data['type'] ?? 'Other',
            'description'    => trim($data['description'] ?? ''),
            'amount'         => trim($data['amount'] ?? ''),
            'eligibility'    => trim($data['eligibility'] ?? ''),
            'deadline'       => $data['deadline'] ?? null,
            'external_link'  => trim($data['external_link'] ?? ''),
            'status'         => $data['status'] ?? 'active',
        ];
        $result = $this->db->update('scholarships', array_filter($updateData, fn($v) => $v !== null), ['id' => $id]);
        logActivity('update_scholarship', $_SESSION['user_id'] ?? null, [
            'scholarship_id' => $id,
            'title'         => $data['title'] ?? '',
        ]);
        return (bool)$result;
    }

    public function delete(int $id): bool {
        $scholarship = $this->getById($id);
        $result = $this->db->delete('scholarships', ['id' => $id]);
        logActivity('delete_scholarship', $_SESSION['user_id'] ?? null, [
            'scholarship_id' => $id,
            'title'         => $scholarship['title'] ?? '',
        ]);
        return (bool)$result;
    }

    public function getBySlug(string $slug) {
        return $this->db->fetch("SELECT * FROM scholarships WHERE slug = ?", [$slug]);
    }

    public function getTypes(): array {
        $rows = $this->db->fetchAll("SELECT DISTINCT type FROM scholarships WHERE type IS NOT NULL AND type != '' ORDER BY type ASC");
        return array_column($rows, 'type');
    }
}