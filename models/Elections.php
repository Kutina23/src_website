<?php
class Elections {
    private $db;

    public function __construct($database) {
        $this->db = $database;
    }

    // ─── READ ───────────────────────────────────────────────────────────────

    public function getAll(array $filters = []): array {
        $sql = "SELECT id, title, description, position, election_date, start_time, end_time, location, status, is_active
                FROM elections WHERE 1=1";
        $params = [];

        if (!empty($filters['status'])) {
            $sql .= " AND status = ?";
            $params[] = $filters['status'];
        }
        if (!empty($filters['position'])) {
            $sql .= " AND position = ?";
            $params[] = $filters['position'];
        }
        if (!empty($filters['search'])) {
            $sql .= " AND (title LIKE ? OR description LIKE ? OR position LIKE ? OR location LIKE ?)";
            $term = "%{$filters['search']}%";
            $params[] = $term; $params[] = $term; $params[] = $term; $params[] = $term;
        }
        if (($filters['include_inactive'] ?? false) === false) {
            $sql .= " AND is_active = 1";
        }

        $order = $filters['order_by'] ?? 'election_date DESC';
        $allowedOrder = ['election_date ASC', 'election_date DESC', 'title ASC', 'title DESC', 'status ASC', 'created_at DESC'];
        $sql .= " ORDER BY " . (in_array($order, $allowedOrder, true) ? $order : 'election_date DESC');

        return $this->db->fetchAll($sql, $params);
    }

    public function getById(int $id): ?array {
        $row = $this->db->fetch(
            "SELECT * FROM elections WHERE id = ?",
            [$id]
        );
        return $row ?: null;
    }

    public function getUpcoming(): array {
        return $this->db->fetchAll(
            "SELECT id, title, description, position, election_date, start_time, end_time, location, status
             FROM elections
             WHERE status = 'UPCOMING' AND is_active = 1
             ORDER BY election_date ASC LIMIT 3"
        );
    }

    public function getAllActive(): array {
        return $this->db->fetchAll(
            "SELECT id, title, description, position, election_date, start_time, end_time, location, status
             FROM elections
             WHERE is_active = 1
             ORDER BY election_date DESC"
        );
    }

    public function getActiveElection(): ?array {
        $row = $this->db->fetch(
            "SELECT id, title, position, election_date, start_time, end_time, location, status
             FROM elections
             WHERE status = 'ONGOING' AND is_active = 1
             LIMIT 1"
        );
        return $row ?: null;
    }

    public function getEligibilityByElection(int $electionId): array {
        return $this->db->fetchAll(
            "SELECT criteria_text FROM election_eligibility WHERE election_id = ? ORDER BY display_order ASC",
            [$electionId]
        );
    }

    // ─── WRITE ──────────────────────────────────────────────────────────────

    public function create(array $data): int {
        $id = $this->db->insert('elections', [
            'title'              => trim($data['title'] ?? ''),
            'description'        => trim($data['description'] ?? ''),
            'position'           => trim($data['position'] ?? ''),
            'election_date'     => trim($data['election_date'] ?? ''),
            'start_time'         => trim($data['start_time'] ?? ''),
            'end_time'           => trim($data['end_time'] ?? ''),
            'location'           => trim($data['location'] ?? ''),
            'status'             => $data['status'] ?? 'UPCOMING',
            'is_active'          => 1,
        ]);
        logActivity('create_election', $_SESSION['user_id'] ?? null, [
            'election_id' => $id,
            'title'       => $data['title'] ?? '',
        ]);
        return (int)$id;
    }

    public function update(int $id, array $data): bool {
        $updateData = [
            'title'         => trim($data['title'] ?? ''),
            'description'   => trim($data['description'] ?? ''),
            'position'      => trim($data['position'] ?? ''),
            'election_date' => trim($data['election_date'] ?? ''),
            'start_time'    => trim($data['start_time'] ?? ''),
            'end_time'      => trim($data['end_time'] ?? ''),
            'location'      => trim($data['location'] ?? ''),
            'status'        => $data['status'] ?? 'UPCOMING',
        ];
        $result = $this->db->update('elections', $updateData, ['id' => $id]);
        logActivity('update_election', $_SESSION['user_id'] ?? null, [
            'election_id' => $id,
            'title'       => $data['title'] ?? '',
        ]);
        return (bool)$result;
    }

    public function delete(int $id): bool {
        $election = $this->getById($id);
        $result   = $this->db->delete('elections', 'id = ' . (int)$id);
        logActivity('delete_election', $_SESSION['user_id'] ?? null, [
            'election_id' => $id,
            'title'       => $election['title'] ?? '',
        ]);
        return (bool)$result;
    }

    public function softDelete(int $id): bool {
        return $this->db->update('elections', ['is_active' => 0], ['id' => $id]);
    }

    // ─── STATS ──────────────────────────────────────────────────────────────

    public function getStats(): array {
        return [
            'total'     => (int)$this->db->fetch("SELECT COUNT(*) as n FROM elections WHERE is_active = 1")['n'],
            'upcoming'  => (int)$this->db->fetch("SELECT COUNT(*) as n FROM elections WHERE is_active = 1 AND status = 'UPCOMING'")['n'],
            'ongoing'   => (int)$this->db->fetch("SELECT COUNT(*) as n FROM elections WHERE is_active = 1 AND status = 'ONGOING'")['n'],
            'completed' => (int)$this->db->fetch("SELECT COUNT(*) as n FROM elections WHERE is_active = 1 AND status = 'COMPLETED'")['n'],
            'cancelled' => (int)$this->db->fetch("SELECT COUNT(*) as n FROM elections WHERE is_active = 1 AND status = 'CANCELLED'")['n'],
            'positions' => $this->getDistinctPositions(),
        ];
    }

    public function getDistinctPositions(): array {
        return $this->db->fetchAll(
            "SELECT DISTINCT position FROM elections WHERE is_active = 1 AND position IS NOT NULL AND position <> '' ORDER BY position"
        );
    }

    // ─── HELPERS ────────────────────────────────────────────────────────────

    public function getStatusOptions(): array {
        return ['UPCOMING', 'ONGOING', 'COMPLETED', 'CANCELLED'];
    }

    public function getPositionOptions(): array {
        return [
            'SRC President',
            'SRC Vice President',
            'Financial Secretary',
            'General Secretary',
            'PRO',
            'Women\'s Commissioner',
            'SRC EC',
            'Committee Chairs',
            'Hall Representatives',
            'Faculty Representatives',
        ];
    }

    public function getStatusBadgeClass(string $status): string {
        return match ($status) {
            'UPCOMING'  => 'badge-upcoming',
            'ONGOING'   => 'badge-ongoing',
            'COMPLETED' => 'badge-completed',
            'CANCELLED' => 'badge-cancelled',
            default     => '',
        };
    }

    public function getStatusIcon(string $status): string {
        return match ($status) {
            'UPCOMING'  => 'calendar-event',
            'ONGOING'   => 'broadcast',
            'COMPLETED' => 'check2-circle',
            'CANCELLED' => 'x-circle',
            default     => 'circle',
        };
    }
}
?>