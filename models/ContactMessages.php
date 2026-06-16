<?php
class ContactMessages {
    private $db;

    public function __construct($database) {
        $this->db = $database;
    }

    public function create($data) {
        $id = $this->db->insert('contact_messages', [
            'full_name' => $data['full_name'],
            'student_id' => $data['student_id'] ?? null,
            'email' => $data['email'],
            'category' => $data['category'],
            'message' => $data['message'],
            'status' => 'NEW',
            'created_at' => date('Y-m-d H:i:s')
        ]);
        return $id;
    }

    public function getAll($filters = []) {
        $sql = "SELECT cm.*, 
                       u1.first_name as assigned_first, u1.last_name as assigned_last
                FROM contact_messages cm
                LEFT JOIN users u1 ON cm.assigned_to = u1.id
                WHERE 1=1";
        $params = [];

        if (!empty($filters['status'])) {
            $sql .= " AND cm.status = ?";
            $params[] = $filters['status'];
        }

        if (!empty($filters['category'])) {
            $sql .= " AND cm.category = ?";
            $params[] = $filters['category'];
        }

        if (!empty($filters['search'])) {
            $sql .= " AND (cm.full_name LIKE ? OR cm.email LIKE ? OR cm.message LIKE ?)";
            $params[] = "%{$filters['search']}%";
            $params[] = "%{$filters['search']}%";
            $params[] = "%{$filters['search']}%";
        }

        if (!empty($filters['assigned_to'])) {
            $sql .= " AND cm.assigned_to = ?";
            $params[] = $filters['assigned_to'];
        }

        $sql .= " ORDER BY cm.created_at DESC";
        return $this->db->fetchAll($sql, $params);
    }

    public function getById($id) {
        return $this->db->fetch(
            "SELECT cm.*, 
                   u1.first_name as assigned_first, u1.last_name as assigned_last, u1.email as assigned_email
            FROM contact_messages cm
            LEFT JOIN users u1 ON cm.assigned_to = u1.id
            WHERE cm.id = ?",
            [$id]
        );
    }

    public function updateStatus($id, $status) {
        return $this->db->update('contact_messages', [
            'status' => $status,
            'updated_at' => date('Y-m-d H:i:s')
        ], ['id' => $id]);
    }

    public function assign($id, $assignedTo) {
        return $this->db->update('contact_messages', [
            'assigned_to' => $assignedTo,
            'status' => 'IN_PROGRESS',
            'updated_at' => date('Y-m-d H:i:s')
        ], ['id' => $id]);
    }

    public function addResponse($id, $response) {
        return $this->db->update('contact_messages', [
            'response' => $response,
            'status' => 'RESPONDED',
            'updated_at' => date('Y-m-d H:i:s')
        ], ['id' => $id]);
    }

    public function delete($id) {
        return $this->db->delete('contact_messages', ['id' => $id]);
    }

    public function countAll() {
        return $this->db->fetch("SELECT COUNT(*) as count FROM contact_messages")["count"];
    }

    public function countByStatus($status) {
        return $this->db->fetch("SELECT COUNT(*) as count FROM contact_messages WHERE status = ?", [$status])["count"];
    }

    public function getCategories() {
        $sql = "SELECT DISTINCT category FROM contact_messages WHERE category IS NOT NULL AND category != '' ORDER BY category";
        return array_column($this->db->fetchAll($sql), 'category');
    }

    public function getStaffMembers() {
        return $this->db->fetchAll(
            "SELECT u.id, u.first_name, u.last_name, u.email, r.name as role
             FROM users u
             JOIN roles r ON r.id = u.role_id
             WHERE u.is_active = TRUE AND r.name IN ('PRO', 'DIRECTOR ICT', 'DEAN')
             ORDER BY r.name, u.first_name"
        );
    }
}