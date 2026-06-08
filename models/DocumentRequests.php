<?php
class DocumentRequests {
    private $db;

    public function __construct($database) {
        $this->db = $database;
    }

    // ── Listing & Search ──────────────────────────────────

    public function getAll($filters = []) {
        $sql = "SELECT dr.*,
                       u.first_name, u.last_name, u.email, u.student_id, u.role_id, r.name as role,
                       p1.first_name as processed_first, p1.last_name as processed_last
                FROM document_requests dr
                JOIN users u ON dr.user_id = u.id
                LEFT JOIN roles r ON u.role_id = r.id
                LEFT JOIN users p1 ON dr.processed_by = p1.id
                WHERE 1=1";
        $params = [];

        if (!empty($filters['status'])) {
            $sql .= " AND dr.status = ?";
            $params[] = $filters['status'];
        }

        if (!empty($filters['document_type'])) {
            $sql .= " AND dr.document_type = ?";
            $params[] = $filters['document_type'];
        }

        if (!empty($filters['search'])) {
            $sql .= " AND (u.first_name LIKE ? OR u.last_name LIKE ?
                             OR u.student_id LIKE ? OR dr.request_token LIKE ?
                             OR dr.document_type LIKE ? OR dr.purpose LIKE ?)";
            $params[] = "%{$filters['search']}%";
            $params[] = "%{$filters['search']}%";
            $params[] = "%{$filters['search']}%";
            $params[] = "%{$filters['search']}%";
            $params[] = "%{$filters['search']}%";
            $params[] = "%{$filters['search']}%";
        }

        $sql .= " ORDER BY dr.requested_at DESC";
        return $this->db->fetchAll($sql, $params);
    }

    public function getById($id) {
        return $this->db->fetch(
            "SELECT dr.*,
                    u.first_name, u.last_name, u.email, u.student_id,
                    r.name as role,
                    p1.first_name as processed_first, p1.last_name as processed_last
             FROM document_requests dr
             JOIN users u ON dr.user_id = u.id
             LEFT JOIN roles r ON u.role_id = r.id
             LEFT JOIN users p1 ON dr.processed_by = p1.id
             WHERE dr.id = ?",
            [$id]
        );
    }

    // ── Status Updates ────────────────────────────────────

    public function processRequest($id, $processedBy = null) {
        return $this->db->update('document_requests', [
            'status'      => 'PROCESSING',
            'processed_by'=> $processedBy,
            'processed_at'=> date('Y-m-d H:i:s'),
            'updated_at'  => date('Y-m-d H:i:s')
        ], ['id' => $id]);
    }

    public function markReady($id) {
        return $this->db->update('document_requests', [
            'status'     => 'READY',
            'updated_at' => date('Y-m-d H:i:s')
        ], ['id' => $id]);
    }

    public function markCollected($id) {
        return $this->db->update('document_requests', [
            'status'      => 'COLLECTED',
            'collected_at'=> date('Y-m-d H:i:s'),
            'updated_at'  => date('Y-m-d H:i:s')
        ], ['id' => $id]);
    }

    public function rejectRequest($id, $remarks) {
        return $this->db->update('document_requests', [
            'status'     => 'REJECTED',
            'remarks'    => $remarks,
            'updated_at' => date('Y-m-d H:i:s')
        ], ['id' => $id]);
    }

    public function updateStatus($id, $status, $remarks = null) {
        $data = ['status' => $status, 'updated_at' => date('Y-m-d H:i:s')];
        if ($remarks !== null) {
            $data['remarks'] = $remarks;
        }
        if ($status === 'COLLECTED') {
            $data['collected_at'] = date('Y-m-d H:i:s');
        }
        if ($status === 'PROCESSING') {
            $data['processed_by'] = $_SESSION['user_id'] ?? null;
            $data['processed_at'] = date('Y-m-d H:i:s');
        }
        return $this->db->update('document_requests', $data, ['id' => $id]);
    }

    // ── Totals ────────────────────────────────────────────

    public function countAll() {
        return $this->db->fetch("SELECT COUNT(*) as count FROM document_requests")["count"];
    }

    public function countByStatus($status) {
        return $this->db->fetch("SELECT COUNT(*) as count FROM document_requests WHERE status = ?", [$status])["count"];
    }

    public function countPending() {
        return $this->countByStatus('PENDING');
    }

    public function countProcessing() {
        return $this->countByStatus('PROCESSING');
    }

    public function countReady() {
        return $this->countByStatus('READY');
    }

    // ── Options ───────────────────────────────────────────

    public function getDocumentTypes() {
        $sql = "SELECT DISTINCT document_type FROM document_requests
                WHERE document_type IS NOT NULL AND document_type != ''
                ORDER BY document_type";
        return array_column($this->db->fetchAll($sql), 'document_type');
    }
}
