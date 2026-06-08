<?php
class ExecutiveMembers {
    private $db;

    public function __construct($database) {
        $this->db = $database;
    }

public function getAll() {
        $sql = "SELECT cm.id, cm.user_id, cm.position, cm.department_id, cm.term_start, cm.term_end,
                       cm.is_active, cm.display_order, cm.profile_image_id,
                       u.first_name, u.last_name, u.email, u.phone,
                       u.staff_id, u.office_location, u.office_hours, u.appointment_info, u.key_responsibilities,
                       d.name as department, m.file_path as profile_image_path
                FROM council_members cm
                JOIN users u ON cm.user_id = u.id
                LEFT JOIN departments d ON cm.department_id = d.id
                LEFT JOIN media m ON cm.profile_image_id = m.id
                ORDER BY cm.display_order ASC, cm.position ASC";
        return $this->db->fetchAll($sql);
    }

    public function getActive() {
        $sql = "SELECT cm.id, cm.user_id, cm.position, cm.department_id, cm.term_start, cm.term_end,
                       cm.is_active, cm.display_order, cm.profile_image_id,
                       u.first_name, u.last_name, u.email, u.phone,
                       d.name as department, m.file_path as profile_image_path
                FROM council_members cm
                JOIN users u ON cm.user_id = u.id
                LEFT JOIN departments d ON cm.department_id = d.id
                LEFT JOIN media m ON cm.profile_image_id = m.id
                WHERE cm.is_active = TRUE
                ORDER BY cm.display_order ASC, cm.position ASC";
        return $this->db->fetchAll($sql);
    }

    public function getById($id) {
        $sql = "SELECT cm.*, u.first_name, u.last_name, u.email, u.phone,
                       u.staff_id, u.office_location, u.office_hours, u.appointment_info, u.key_responsibilities,
                       m.file_path as profile_image_path
                FROM council_members cm
                JOIN users u ON cm.user_id = u.id
                LEFT JOIN media m ON cm.profile_image_id = m.id
                WHERE cm.id = ?";
        return $this->db->fetch($sql, [$id]);
    }

    public function getByPosition($position) {
        $sql = "SELECT cm.*, u.first_name, u.last_name, u.email, u.phone,
                       u.staff_id, u.office_location, u.office_hours, u.appointment_info, u.key_responsibilities,
                       m.file_path as profile_image_path
                FROM council_members cm
                JOIN users u ON cm.user_id = u.id
                LEFT JOIN media m ON cm.profile_image_id = m.id
                WHERE cm.position = ? AND cm.is_active = TRUE
                LIMIT 1";
        return $this->db->fetch($sql, [$position]);
    }

    public function create($data) {
        $memberId = $this->db->insert("council_members", $data);
        logActivity("create_executive_member", $_SESSION["user_id"] ?? null, ["member_id" => $memberId, "position" => $data["position"]]);
        return $memberId;
    }

    public function update($id, $data) {
        $result = $this->db->update("council_members", $data, ["id" => $id]);
        logActivity("update_executive_member", $_SESSION["user_id"] ?? null, ["member_id" => $id]);
        return $result;
    }

    public function delete($id) {
        $member = $this->getById($id);
        $result = $this->db->execute("DELETE FROM council_members WHERE id = ?", [$id]);
        if ($result && $member) {
            logActivity("delete_executive_member", $_SESSION["user_id"] ?? null, ["member_id" => $id, "position" => $member["position"]]);
        }
        return $result;
    }

    public function updateProfileImage($memberId, $mediaId) {
        return $this->update($memberId, ["profile_image_id" => $mediaId]);
    }

    public function getAvailablePositions() {
        return [
            "SRC President",
            "Vice President",
            "General Secretary",
            "SRC Organizer",
            "SRC Finance Officer",
            "SRC Chief Justice",
            "SRC Chief Imam",
            "SRC Chaplain",
            "Cadet Commanding Officer",
            "Women's Commissioner",
            "Deputy EC",
            "Rt. Hon. Speaker",
            "Rt. Hon. Deputy Speaker",
            "Entertainment Secretary",
            "Clerk of General Assembly",
            "SRC Vice President",
            "SRC Security Coordinator",
            "SRC EC",
            "PRO",
            "SRC Library Secretary",
            "SRC Health and Welfare Secretary",
            "SRC GNUTS Ambassador",
            "Sports Secretary",
            "Dean of Student Affairs",
            "Tech & Innovation Committee Secretary",
            "Financial Secretary",
            "SRC Treasurer",
            "International Students Representative",
            "Local Students Representative",
            "PWD Representative",
            "Accommodation Commissioner"
        ];
    }

    public function getAvailableUsers() {
        $sql = "SELECT id, first_name, last_name, email FROM users WHERE is_active = TRUE ORDER BY first_name, last_name";
        return $this->db->fetchAll($sql);
    }

    public function getDepartments() {
        return $this->db->fetchAll("SELECT id, name FROM departments ORDER BY name");
    }
}
?>

