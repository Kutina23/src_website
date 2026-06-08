<?php
class Council {
    private $db;

    public function __construct($database) {
        $this->db = $database;
    }

    public function getAllActive() {
        $sql = "SELECT u.id, u.first_name, u.last_name, u.email, u.phone, u.bio, u.staff_id, u.office_location, u.office_hours, u.appointment_info, u.key_responsibilities,
                        r.name as role, d.name as department, cm.position, cm.term_start, cm.term_end,
                        m.file_path as profile_image_path
                 FROM users u
                 JOIN roles r ON u.role_id = r.id
                 JOIN council_members cm ON u.id = cm.user_id
                 LEFT JOIN departments d ON u.department_id = d.id
                 LEFT JOIN media m ON cm.profile_image_id = m.id
                 WHERE cm.is_active = TRUE
                 ORDER BY cm.display_order ASC";
        return $this->db->fetchAll($sql);
    }

    public function getById($id) {
        $sql = "SELECT u.id, u.first_name, u.last_name, u.email, u.phone, u.bio, u.staff_id, u.office_location, u.office_hours, u.appointment_info, u.key_responsibilities,
                        r.name as role, d.name as department,
                        cm.position, cm.term_start, cm.term_end,
                        m.file_path as profile_image_path
                 FROM users u
                 JOIN roles r ON u.role_id = r.id
                 JOIN council_members cm ON u.id = cm.user_id
                 LEFT JOIN departments d ON u.department_id = d.id
                 LEFT JOIN media m ON cm.profile_image_id = m.id
                 WHERE u.id = ?";
        return $this->db->fetch($sql, [$id]);
    }

    public function getByPosition($position) {
        $sql = "SELECT u.id, u.first_name, u.last_name, u.email, u.phone, u.bio, u.staff_id, u.office_location, u.office_hours, u.appointment_info, u.key_responsibilities,
                        r.name as role, d.name as department,
                        cm.position, cm.term_start, cm.term_end,
                        m.file_path as profile_image_path
                 FROM users u
                 JOIN roles r ON u.role_id = r.id
                 JOIN council_members cm ON u.id = cm.user_id
                 LEFT JOIN departments d ON u.department_id = d.id
                 LEFT JOIN media m ON cm.profile_image_id = m.id
                 WHERE cm.position = ? AND cm.is_active = TRUE
                 LIMIT 1";
        return $this->db->fetch($sql, [$position]);
    }
}
?>