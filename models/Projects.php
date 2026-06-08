<?php
class Projects {
    private $db;

    public function __construct($database) {
        $this->db = $database;
    }

    // ─── READ ───────────────────────────────────────────────────────

    public function getAllActive(array $filters = [], int $limit = 0): array {
        $sql = "SELECT id, title, description, category, image_path, media_id, link_url, status, display_order
                FROM projects WHERE is_active = 1";
        $params = [];

        if (!empty($filters['status'])) {
            $sql .= " AND status = ?";
            $params[] = $filters['status'];
        }

        if (!empty($filters['category'])) {
            $sql .= " AND category = ?";
            $params[] = $filters['category'];
        }

        $sql .= " ORDER BY display_order ASC, created_at DESC";

        if ($limit > 0) {
            $sql .= " LIMIT ?";
            $params[] = $limit;
        }

        return $this->db->fetchAll($sql, $params);
    }

    public function getAll(array $filters = []): array {
        $sql = "SELECT id, title, description, category, image_path, media_id, link_url, status, display_order, is_active,
                       created_at, updated_at
                FROM projects WHERE 1=1";
        $params = [];

        if (!empty($filters['status'])) {
            $sql .= " AND status = ?";
            $params[] = $filters['status'];
        }

        if (!empty($filters['category'])) {
            $sql .= " AND category = ?";
            $params[] = $filters['category'];
        }

        if (($filters['include_inactive'] ?? false) === false) {
            $sql .= " AND is_active = 1";
        }

        $sql .= " ORDER BY display_order ASC, created_at DESC";
        return $this->db->fetchAll($sql, $params);
    }

    public function getById(int $id): ?array {
        $row = $this->db->fetch(
            "SELECT id, title, description, category, image_path, media_id, link_url, status, display_order, is_active,
                    created_at, updated_at
             FROM projects WHERE id = ?",
            [$id]
        );
        return $row ?: null;
    }

    public function getCount(): int {
        $row = $this->db->fetch("SELECT COUNT(*) as n FROM projects WHERE is_active = 1");
        return (int)($row['n'] ?? 0);
    }

    // ─── WRITE ──────────────────────────────────────────────────────

    public function create(array $data): int {
        $id = $this->db->insert('projects', [
            'title'         => trim($data['title'] ?? ''),
            'description'   => trim($data['description'] ?? ''),
            'category'      => $data['category'] ?? 'Other',
            'image_path'    => trim($data['image_path'] ?? ''),
            'media_id'      => !empty($data['media_id']) ? (int)$data['media_id'] : null,
            'link_url'      => trim($data['link_url'] ?? ''),
            'status'        => $data['status'] ?? 'upcoming',
            'display_order' => (int)($data['display_order'] ?? 0),
            'is_active'     => 1,
        ]);
        logActivity('create_project', $_SESSION['user_id'] ?? null, [
            'project_id' => $id,
            'title'      => $data['title'] ?? '',
        ]);
        return (int)$id;
    }

    public function update(int $id, array $data): bool {
        $updateData = [
            'title'         => trim($data['title'] ?? ''),
            'description'   => trim($data['description'] ?? ''),
            'category'      => $data['category'] ?? 'Other',
            'image_path'    => trim($data['image_path'] ?? ''),
            'media_id'      => !empty($data['media_id']) ? (int)$data['media_id'] : null,
            'link_url'      => trim($data['link_url'] ?? ''),
            'status'        => $data['status'] ?? 'upcoming',
            'display_order' => (int)($data['display_order'] ?? 0),
            'is_active'     => isset($data['is_active']) ? (int)$data['is_active'] : null,
        ];
        // Strip nulls so partial updates don't zero out data
        $updateData = array_filter($updateData, fn($v) => $v !== null);
        $result = $this->db->update('projects', $updateData, ['id' => $id]);
        logActivity('update_project', $_SESSION['user_id'] ?? null, [
            'project_id' => $id,
            'title'      => $data['title'] ?? '',
        ]);
        return (bool)$result;
    }

    public function delete(int $id): bool {
        $project = $this->getById($id);
        $result  = $this->db->delete('projects', ['id' => $id]);
        logActivity('delete_project', $_SESSION['user_id'] ?? null, [
            'project_id' => $id,
            'title'      => $project['title'] ?? '',
        ]);
        return (bool)$result;
    }

    // ─── HELPERS ────────────────────────────────────────────────────

    public function getStatusBadge(string $status): string {
        return match ($status) {
            'upcoming' => 'badge-upcoming',
            'ongoing'  => 'badge-info',
            'completed'=> 'badge-completed',
            default    => '',
        };
    }

    public function getAllCategories(): array {
        return ['Academic','Finance','Elections','Welfare','Clubs & Culture','Communication','Sports','Outreach','Infrastructure','Environmental','Other'];
    }

    public function getCommonIcons(): array {
        return [
            'bi-mortarboard'    => 'Academic',
            'bi-currency-dollar'=> 'Finance',
            'bi-box-arrow-in-right' => 'Elections',
            'bi-heart-pulse'    => 'Welfare',
            'bi-palette'        => 'Clubs & Culture',
            'bi-broadcast'      => 'Communication',
            'bi-trophy'         => 'Sports',
            'bi-people'         => 'Outreach',
            'bi-building'       => 'Infrastructure',
            'bi-tree'           => 'Environmental',
        ];
    }
}
