<?php
class Gallery {
    private $db;

    public function __construct($database) {
        $this->db = $database;
    }

    public function getImagesBySection($sectionType, $limit = null) {
        $sql = "SELECT mi.id, mi.caption, m.file_path, m.alt_text
                FROM media_items mi
                JOIN media m ON mi.media_id = m.id
                JOIN media_sections ms ON mi.section_id = ms.id
                WHERE ms.section_type = ? AND ms.is_active = TRUE AND m.file_type = 'IMAGE' AND mi.is_active = TRUE
                ORDER BY mi.display_order ASC";
        
        if ($limit) {
            $sql .= " LIMIT " . (int)$limit;
        }
        
        return $this->db->fetchAll($sql, [$sectionType]);
    }

    public function getVideosBySection($sectionType, $limit = null) {
        $sql = "SELECT mi.id, mi.caption, m.file_path, m.alt_text
                FROM media_items mi
                JOIN media m ON mi.media_id = m.id
                JOIN media_sections ms ON mi.section_id = ms.id
                WHERE ms.section_type = ? AND ms.is_active = TRUE AND m.file_type = 'VIDEO' AND mi.is_active = TRUE
                ORDER BY mi.display_order ASC";
        
        if ($limit) {
            $sql .= " LIMIT " . (int)$limit;
        }
        
        return $this->db->fetchAll($sql, [$sectionType]);
    }

    public function getAllSections() {
        $sql = "SELECT id, title, description, section_type
                FROM media_sections 
                WHERE is_active = TRUE
                ORDER BY display_order ASC";
        return $this->db->fetchAll($sql);
    }

    public function getSectionByTitle($title) {
        return $this->db->fetch("SELECT * FROM media_sections WHERE title = ?", [$title]);
    }

    public function getItemsBySectionId($sectionId, $limit = null) {
        $sql = "SELECT mi.id, mi.caption, m.file_path, m.alt_text, m.file_type
                FROM media_items mi
                JOIN media m ON mi.media_id = m.id
                WHERE mi.section_id = ? AND mi.is_active = TRUE
                ORDER BY mi.display_order ASC";
        
        if ($limit) {
            $sql .= " LIMIT " . (int)$limit;
        }
        
        return $this->db->fetchAll($sql, [$sectionId]);
    }

    public function getHeroImage() {
        $sql = "SELECT m.file_path, m.alt_text
                FROM media m
                JOIN dean_images di ON m.id = di.media_id
                WHERE di.image_type = 'HERO' AND di.is_active = TRUE
                ORDER BY di.display_order ASC
                LIMIT 1";
        return $this->db->fetch($sql);
    }

    public function getDeanImage() {
        $sql = "SELECT m.file_path, m.alt_text
                FROM media m
                JOIN dean_images di ON m.id = di.media_id
                WHERE di.image_type = 'HERO' AND di.is_active = TRUE
                ORDER BY di.display_order ASC, di.created_at DESC
                LIMIT 1";
        return $this->db->fetch($sql);
    }

    public function getDeanImages($limit = 100) {
        $sql = "SELECT m.file_path, m.alt_text, di.display_order
                FROM media m
                JOIN dean_images di ON m.id = di.media_id
                WHERE di.image_type = 'HERO' AND di.is_active = TRUE
                ORDER BY di.display_order ASC, di.created_at DESC";
        
        if ($limit) {
            $sql .= " LIMIT " . (int)$limit;
        }
        
        return $this->db->fetchAll($sql);
    }

    public function getPresidentImage() {
        $sql = "SELECT m.file_path, m.alt_text
                FROM media m
                JOIN president_images pi ON m.id = pi.media_id
                WHERE pi.image_type = 'ABOUT_CARD' AND pi.is_active = TRUE
                ORDER BY pi.display_order ASC, pi.created_at DESC
                LIMIT 1";
        return $this->db->fetch($sql);
    }

    public function getGalleryImages($limit = 10) {
        $section = $this->getSectionByTitle('Gallery');
        if (!$section) {
            return [];
        }
        return $this->getItemsBySectionId($section['id'], $limit);
    }
}
?>
