<?php
class Complaints {
    private $db;

    public function __construct($database) {
        $this->db = $database;
    }

    // ── Listing & Search ──────────────────────────────────

    public function getAll($filters = []) {
        $sql = "SELECT c.*,
                       u1.first_name as assigned_first, u1.last_name as assigned_last, u1.email as assigned_email
                FROM complaints c
                LEFT JOIN users u1 ON c.assigned_to = u1.id
                WHERE 1=1";
        $params = [];

        if (!empty($filters['status'])) {
            $sql .= " AND c.status = ?";
            $params[] = $filters['status'];
        }

        if (!empty($filters['category'])) {
            $sql .= " AND c.category = ?";
            $params[] = $filters['category'];
        }

        if (!empty($filters['priority'])) {
            $sql .= " AND c.priority = ?";
            $params[] = $filters['priority'];
        }

        if (!empty($filters['search'])) {
            $sql .= " AND (c.subject LIKE ? OR c.description LIKE ? OR c.complaint_token LIKE ?)";
            $params[] = "%{$filters['search']}%";
            $params[] = "%{$filters['search']}%";
            $params[] = "%{$filters['search']}%";
        }

        if (!empty($filters['assigned_to'])) {
            $sql .= " AND c.assigned_to = ?";
            $params[] = $filters['assigned_to'];
        }

        if (!empty($filters['unassigned'])) {
            $sql .= " AND c.assigned_to IS NULL";
        }

        $sql .= " ORDER BY c.created_at DESC";
        return $this->db->fetchAll($sql, $params);
    }

    public function getById($id) {
        return $this->db->fetch(
            "SELECT c.*,
                    u1.first_name as assigned_first, u1.last_name as assigned_last, u1.email as assigned_email
             FROM complaints c
             LEFT JOIN users u1 ON c.assigned_to = u1.id
             WHERE c.id = ?",
            [$id]
        );
    }

    // ── CRUD ──────────────────────────────────────────────

    public function updateStatus($id, $status, $resolution = null) {
        $data = ['status' => $status, 'updated_at' => date('Y-m-d H:i:s')];
        if ($resolution !== null) {
            $data['resolution'] = $resolution;
        }
        if ($status === 'RESOLVED' || $status === 'CLOSED') {
            $data['resolved_at'] = date('Y-m-d H:i:s');
        }
        return $this->db->update('complaints', $data, ['id' => $id]);
    }

    public function assign($id, $assignedTo, $note = null) {
        $data = ['assigned_to' => $assignedTo, 'updated_at' => date('Y-m-d H:i:s')];
        if ($note !== null) {
            $existing = $this->getById($id);
            $data['resolution'] = ($existing['resolution'] ?? '') . "\n[Assignment Note] " . $note;
        }
        return $this->db->update('complaints', $data, ['id' => $id]);
    }

    public function updatePriority($id, $priority) {
        return $this->db->update('complaints', [
            'priority'    => $priority,
            'updated_at'  => date('Y-m-d H:i:s')
        ], ['id' => $id]);
    }

    public function assignAndPatchStatus($id, $assignedTo, $status) {
        $data = ['assigned_to' => $assignedTo, 'status' => $status, 'updated_at' => date('Y-m-d H:i:s')];
        if ($status === 'RESOLVED' || $status === 'CLOSED') {
            $data['resolved_at'] = date('Y-m-d H:i:s');
        }
        return $this->db->update('complaints', $data, ['id' => $id]);
    }

    // ── Totals ────────────────────────────────────────────

    public function countAll() {
        return $this->db->fetch("SELECT COUNT(*) as count FROM complaints")["count"];
    }

    public function countByStatus($status) {
        return $this->db->fetch("SELECT COUNT(*) as count FROM complaints WHERE status = ?", [$status])["count"];
    }

    public function countByPriority($priority) {
        return $this->db->fetch("SELECT COUNT(*) as count FROM complaints WHERE priority = ?", [$priority])["count"];
    }

    public function countOpen() {
        return $this->countByStatus('OPEN');
    }

    public function countInProgress() {
        return $this->countByStatus('IN_PROGRESS');
    }

    public function countUnassigned() {
        return $this->db->fetch("SELECT COUNT(*) as count FROM complaints WHERE assigned_to IS NULL")["count"];
    }

    // ── Options ───────────────────────────────────────────

    public function getCategories() {
        $sql = "SELECT DISTINCT category FROM complaints WHERE category IS NOT NULL AND category != '' ORDER BY category";
        return array_column($this->db->fetchAll($sql), 'category');
    }

    public function getStaffMembers() {
        return $this->db->fetchAll(
            "SELECT u.id, u.first_name, u.last_name, u.email, r.name as role
             FROM users u
             JOIN roles r ON r.id = u.role_id
             WHERE u.is_active = TRUE AND u.role_id IN (
                 SELECT id FROM roles WHERE name = 'PRO'
                 UNION SELECT id FROM roles WHERE name = 'DIRECTOR ICT'
                 UNION SELECT id FROM roles WHERE name = 'DEAN'
             )
             ORDER BY r.name, u.first_name"
        );
    }
}
