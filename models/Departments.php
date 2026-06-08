<?php
class Departments {
    private $db;

    public function __construct($database) {
        $this->db = $database;
    }

    public function getAll() {
        $sql = "SELECT d.id, d.name, d.code, d.dean_id, d.created_at,
                       u.first_name as dean_first, u.last_name as dean_last,
                       COUNT(u2.id) as user_count
                FROM departments d
                LEFT JOIN users u ON d.dean_id = u.id
                LEFT JOIN users u2 ON u2.department_id = d.id
                GROUP BY d.id
                ORDER BY d.name ASC";
        return $this->db->fetchAll($sql);
    }

    public function getById($id) {
        $sql = "SELECT d.id, d.name, d.code, d.dean_id, d.created_at,
                       u.first_name as dean_first, u.last_name as dean_last,
                       u.email as dean_email
                FROM departments d
                LEFT JOIN users u ON d.dean_id = u.id
                WHERE d.id = ?";
        return $this->db->fetch($sql, [$id]);
    }

    public function create($data) {
        $deptId = $this->db->insert("departments", [
            "name" => $data["name"],
            "code" => $data["code"],
            "dean_id" => $data["dean_id"] ?? null
        ]);
        logActivity("create_department", $_SESSION["user_id"] ?? null, ["department_id" => $deptId, "name" => $data["name"]]);
        return $deptId;
    }

    public function update($id, $data) {
        $result = $this->db->update("departments", $data, ["id" => $id]);
        logActivity("update_department", $_SESSION["user_id"] ?? null, ["department_id" => $id, "name" => $data["name"] ?? ""]);
        return $result;
    }

    public function delete($id) {
        $dept = $this->getById($id);
        $userCount = $this->db->fetch("SELECT COUNT(*) as count FROM users WHERE department_id = ?", [$id])["count"];
        
        if ($userCount > 0) {
            return false;
        }
        
        $result = $this->db->execute("DELETE FROM departments WHERE id = ?", [$id]);
        if ($result && $dept) {
            logActivity("delete_department", $_SESSION["user_id"] ?? null, ["department_id" => $id, "name" => $dept["name"]]);
        }
        return $result;
    }

    public function getAllUsers() {
        return $this->db->fetchAll("SELECT id, first_name, last_name, email FROM users WHERE is_active = TRUE ORDER BY first_name, last_name");
    }

    public function countUsers($deptId) {
        return $this->db->fetch("SELECT COUNT(*) as count FROM users WHERE department_id = ?", [$deptId])["count"];
    }
}