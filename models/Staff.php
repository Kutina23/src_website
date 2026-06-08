<?php
class Staff {
    private $db;

    public function __construct($database) {
        $this->db = $database;
    }

      /**
       * Get Dean of Students with profile information
       */
      public function getDeanOfStudents() {
          $sql = "SELECT u.id, u.first_name, u.last_name, u.email, u.phone, u.bio, u.key_responsibilities,
                         u.linkedin, u.facebook, u.tiktok,
                         s.position, s.staff_id, s.office_location, s.office_hours, s.appointment_required,
                         m.file_path as profile_image_path
                  FROM users u
                  JOIN staff s ON u.id = s.user_id
                  LEFT JOIN dean_images di ON u.id = di.user_id AND di.image_type = 'PROFILE' AND di.is_active = TRUE
                  LEFT JOIN media m ON di.media_id = m.id
                  WHERE s.position = 'Dean of Students'
                  LIMIT 1";
          return $this->db->fetch($sql);
      }

      /**
       * Get all staff by position
       */
      public function getByPosition($position) {
          $sql = "SELECT u.id, u.first_name, u.last_name, u.email, u.phone, u.bio, u.key_responsibilities,
                         u.linkedin, u.facebook, u.tiktok,
                         s.position, s.staff_id, s.office_location, s.office_hours, s.appointment_required,
                         m.file_path as profile_image_path
                  FROM users u
                  JOIN staff s ON u.id = s.user_id
                  LEFT JOIN dean_images di ON u.id = di.user_id AND di.image_type = 'PROFILE' AND di.is_active = TRUE
                  LEFT JOIN media m ON di.media_id = m.id
                  WHERE s.position = ?
                  LIMIT 1";
          return $this->db->fetch($sql, [$position]);
      }

      /**
       * Get all staff members
       */
      public function getAll() {
          $sql = "SELECT u.id, u.first_name, u.last_name, u.email, u.phone, u.bio, u.key_responsibilities,
                         u.linkedin, u.facebook, u.tiktok,
                         s.position, s.staff_id, s.office_location, s.office_hours, s.appointment_required,
                         m.file_path as profile_image_path
                  FROM users u
                  JOIN staff s ON u.id = s.user_id
                  LEFT JOIN dean_images di ON u.id = di.user_id AND di.image_type = 'PROFILE' AND di.is_active = TRUE
                  LEFT JOIN media m ON di.media_id = m.id
                  ORDER BY s.position ASC";
          return $this->db->fetchAll($sql);
      }

      /**
       * Get staff by user ID
       */
      public function getById($userId) {
          $sql = "SELECT u.id, u.first_name, u.last_name, u.email, u.phone, u.bio, u.key_responsibilities,
                         u.linkedin, u.facebook, u.tiktok,
                         s.position, s.staff_id, s.office_location, s.office_hours, s.appointment_required,
                         m.file_path as profile_image_path
                  FROM users u
                  JOIN staff s ON u.id = s.user_id
                  LEFT JOIN dean_images di ON u.id = di.user_id AND di.image_type = 'PROFILE' AND di.is_active = TRUE
                  LEFT JOIN media m ON di.media_id = m.id
                  WHERE u.id = ?
                  LIMIT 1";
          return $this->db->fetch($sql, [$userId]);
      }

    /**
     * Create staff record
     */
    public function create($data) {
        $staffId = $this->db->insert("staff", $data);
        logActivity("create_staff_member", $_SESSION["user_id"] ?? null, ["staff_id" => $staffId, "position" => $data["position"]]);
        return $staffId;
    }

    /**
     * Update staff record
     */
    public function update($userId, $data) {
        $result = $this->db->update("staff", $data, ["user_id" => $userId]);
        logActivity("update_staff_member", $_SESSION["user_id"] ?? null, ["user_id" => $userId]);
        return $result;
    }

    /**
     * Delete staff record
     */
    public function delete($userId) {
        $staff = $this->getById($userId);
        $result = $this->db->execute("DELETE FROM staff WHERE user_id = ?", [$userId]);
        if ($result && $staff) {
            logActivity("delete_staff_member", $_SESSION["user_id"] ?? null, ["user_id" => $userId, "position" => $staff["position"]]);
        }
        return $result;
    }

    /**
     * Assign profile image to staff member (Dean)
     */
    public function assignProfileImage($userId, $mediaId) {
        // First, deactivate existing profile images
        $this->db->execute(
            "UPDATE dean_images SET is_active = FALSE 
             WHERE user_id = ? AND image_type = 'PROFILE'",
            [$userId]
        );
        
        // Then insert or update the new profile image
        $sql = "INSERT INTO dean_images (user_id, media_id, image_type, is_active, display_order)
                VALUES (?, ?, 'PROFILE', TRUE, 0)";
        return $this->db->execute($sql, [$userId, $mediaId]);
    }

    /**
     * Get all profile images for a staff member
     */
    public function getProfileImages($userId, $limit = null) {
        $sql = "SELECT m.id, m.file_path, m.alt_text, di.display_order, di.created_at
                FROM media m
                JOIN dean_images di ON m.id = di.media_id
                WHERE di.user_id = ? AND di.image_type = 'PROFILE' AND di.is_active = TRUE
                ORDER BY di.display_order ASC, di.created_at DESC";
        
        if ($limit) {
            $sql .= " LIMIT " . (int)$limit;
        }
        
        return $this->db->fetchAll($sql, [$userId]);
    }
}
?>
