<?php
class Services {
    private $db;

    public function __construct($database) {
        $this->db = $database;
    }

    // ─── READ ───────────────────────────────────────────────────────────────

    public function getAllActive(array $filters = []): array {
        $sql = "SELECT id, title, description, icon, display_order, is_active
                FROM services WHERE is_active = 1";
        $params = [];

        if (!empty($filters['search'])) {
            $sql .= " AND (title LIKE ? OR description LIKE ?)";
            $term = "%{$filters['search']}%";
            $params[] = $term;
            $params[] = $term;
        }

        $order = $filters['order_by'] ?? 'display_order ASC, created_at DESC';
        $allowedOrder = ['display_order ASC', 'display_order DESC', 'title ASC', 'title DESC', 'created_at DESC', 'created_at ASC'];
        $sql .= " ORDER BY " . (in_array($order, $allowedOrder, true) ? $order : 'display_order ASC, created_at DESC');

        return $this->db->fetchAll($sql, $params);
    }

    public function getAll(array $filters = []): array {
        $sql = "SELECT id, title, description, icon, display_order, is_active,
                       created_at, updated_at
                FROM services WHERE 1=1";
        $params = [];

        if (!empty($filters['search'])) {
            $sql .= " AND (title LIKE ? OR description LIKE ?)";
            $term = "%{$filters['search']}%";
            $params[] = $term;
            $params[] = $term;
        }
        if (($filters['include_inactive'] ?? false) === false) {
            $sql .= " AND is_active = 1";
        }

        $order = $filters['order_by'] ?? 'display_order ASC, created_at DESC';
        $allowedOrder = ['display_order ASC', 'display_order DESC', 'title ASC', 'title DESC', 'created_at DESC', 'created_at ASC'];
        $sql .= " ORDER BY " . (in_array($order, $allowedOrder, true) ? $order : 'display_order ASC, created_at DESC');

        return $this->db->fetchAll($sql, $params);
    }

    public function getById(int $id): ?array {
        $row = $this->db->fetch(
            "SELECT id, title, description, icon, display_order, is_active,
                    created_at, updated_at
             FROM services WHERE id = ?",
            [$id]
        );
        return $row ?: null;
    }

    public function getCount(): int {
        $row = $this->db->fetch("SELECT COUNT(*) as n FROM services WHERE is_active = 1");
        return (int)($row['n'] ?? 0);
    }

    // ─── WRITE ──────────────────────────────────────────────────────────────

    public function create(array $data): int {
        $id = $this->db->insert('services', [
            'title'        => trim($data['title'] ?? ''),
            'description'  => trim($data['description'] ?? ''),
            'icon'         => trim($data['icon'] ?? 'bi-star'),
            'display_order' => (int)($data['display_order'] ?? 0),
            'is_active'    => 1,
        ]);
        logActivity('create_service', $_SESSION['user_id'] ?? null, [
            'service_id' => $id,
            'title'      => $data['title'] ?? '',
        ]);
        return (int)$id;
    }

    public function update(int $id, array $data): bool {
        $updateData = [
            'title'         => trim($data['title'] ?? ''),
            'description'   => trim($data['description'] ?? ''),
            'icon'          => trim($data['icon'] ?? 'bi-star'),
            'display_order' => (int)($data['display_order'] ?? 0),
            'is_active'     => isset($data['is_active']) ? (int)$data['is_active'] : null,
        ];
        // Remove null values so we don't overwrite with null
        $updateData = array_filter($updateData, fn($v) => $v !== null);
        $result = $this->db->update('services', $updateData, ['id' => $id]);
        logActivity('update_service', $_SESSION['user_id'] ?? null, [
            'service_id' => $id,
            'title'      => $data['title'] ?? '',
        ]);
        return (bool)$result;
    }

    public function delete(int $id): bool {
        $service = $this->getById($id);
        $result  = $this->db->delete('services', ['id' => $id]);
        logActivity('delete_service', $_SESSION['user_id'] ?? null, [
            'service_id' => $id,
            'title'      => $service['title'] ?? '',
        ]);
        return (bool)$result;
    }

    public function toggleActive(int $id): bool {
        $current = $this->getById($id);
        if (!$current) return false;
        return $this->update($id, ['is_active' => $current['is_active'] ? 0 : 1]);
    }

    // ─── HELPERS ────────────────────────────────────────────────────────────

    public function getStatusBadgeClass(bool $active): string {
        return $active ? 'badge-upcoming' : 'badge-cancelled';
    }

    public function getCommonIcons(): array {
        return [
            'bi-mortarboard'       => 'Academic',
            'bi-currency-dollar'   => 'Finance',
            'bi-box-arrow-in-right'=> 'Elections',
            'bi-heart-pulse'       => 'Welfare',
            'bi-palette'           => 'Clubs & Culture',
            'bi-broadcast'         => 'Communication',
            'bi-clipboard'         => 'Administration',
            'bi-bi-trophy'         => 'Sports',
            'bi-people'            => 'Students',
            'bi-gear'              => 'Operations',
            'bi-shield-check'      => 'Security',
            'bi-book'              => 'Library',
        ];
    }
}
