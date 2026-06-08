<?php
class News {
    private $db;

    public function __construct($database) {
        $this->db = $database;
    }

    public function getLatest($limit = 5) {
        $sql = "SELECT id, title, excerpt, category, published_at
                FROM news 
                WHERE status = 'PUBLISHED' 
                ORDER BY published_at DESC 
                LIMIT ?";
        return $this->db->fetchAll($sql, [$limit]);
    }

    public function getFeatured() {
        // Prefer explicit is_featured flag; fall back to newest published
        $sql = "SELECT id, title, excerpt, published_at, featured_image, category, is_featured
                FROM news 
                WHERE status = 'PUBLISHED' AND is_featured = 1
                ORDER BY published_at DESC 
                LIMIT 1";
        $featured = $this->db->fetch($sql);
        if ($featured) return $featured;
        $sql = "SELECT id, title, excerpt, published_at, featured_image, category, is_featured
                FROM news 
                WHERE status = 'PUBLISHED'
                ORDER BY published_at DESC 
                LIMIT 1";
        return $this->db->fetch($sql);
    }

    public function getAllPublished() {
        $sql = "SELECT * FROM news 
                WHERE status = 'PUBLISHED' 
                ORDER BY published_at DESC";
        return $this->db->fetchAll($sql);
    }

    public function getByCategory($category, $limit = 100) {
        $sql = "SELECT * FROM news 
                WHERE status = 'PUBLISHED' AND category = ?
                ORDER BY published_at DESC
                LIMIT ?";
        return $this->db->fetchAll($sql, [$category, $limit]);
    }

    public function getFeaturedByCategory($category) {
        $sql = "SELECT * FROM news 
                WHERE status = 'PUBLISHED' AND category = ?
                ORDER BY published_at DESC 
                LIMIT 1";
        return $this->db->fetch($sql, [$category]);
    }

    public function getByCategories($categories, $limit = 100) {
        $placeholders = implode(',', array_fill(0, count($categories), '?'));
        $sql = "SELECT * FROM news 
                WHERE status = 'PUBLISHED' AND category IN ($placeholders)
                ORDER BY published_at DESC
                LIMIT ?";
        $params = array_merge($categories, [$limit]);
        return $this->db->fetchAll($sql, $params);
    }

    public function getAllWithFeatured() {
        // Build list: featured article first (is_featured=1), then remaining PUBLISHED articles
        $featuredRow = $this->db->fetch(
            "SELECT * FROM news WHERE status = 'PUBLISHED' AND is_featured = 1 ORDER BY published_at DESC LIMIT 1"
        );
        $excludeId = $featuredRow ? (int)$featuredRow['id'] : 0;
        $sql = "SELECT * FROM news 
                WHERE status = 'PUBLISHED'
                " . ($excludeId ? "AND id != $excludeId" : "") . "
                ORDER BY published_at DESC";
        $rest = $this->db->fetchAll($sql);
        if (!$featuredRow) {
            // No explicit featured: treat newest as featured
            $featuredRow = $rest[0] ?? null;
            if ($featuredRow) $rest = array_slice($rest, 1);
        }
        return [$featuredRow ?? [], $rest];
    }

    public function getStatsByCategory() {
        $sql = "SELECT category, COUNT(*) as count
                FROM news 
                WHERE status = 'PUBLISHED' 
                GROUP BY category
                ORDER BY count DESC";
        return $this->db->fetchAll($sql);
    }

    public function getTotalByCategory($category) {
        $sql = "SELECT COUNT(*) as total FROM news WHERE status = 'PUBLISHED' AND category = ?";
        return (int) $this->db->fetch($sql, [$category])['total'];
    }

    public function getTotalPublished() {
        $sql = "SELECT COUNT(*) as total FROM news WHERE status = 'PUBLISHED'";
        return (int) $this->db->fetch($sql)['total'];
    }

    public function getAll() {
        $sql = "SELECT * FROM news 
                ORDER BY published_at DESC";
        return $this->db->fetchAll($sql);
    }

    public function getById($id) {
        $sql = "SELECT * FROM news WHERE id = ?";
        return $this->db->fetch($sql, [$id]);
    }

    public function create($data) {
        $keys = array_keys($data);
        $placeholders = array_map(fn($k) => ":$k", $keys);
        $sql = "INSERT INTO news (" . implode(", ", $keys) . ") VALUES (" . implode(", ", $placeholders) . ")";
        $stmt = $this->db->getConnection()->prepare($sql);
        $result = $stmt->execute($data);
        
        // Send email notification if news is being published immediately
        if ($result && isset($data['status']) && $data['status'] === 'PUBLISHED') {
            $insertId = $this->db->getConnection()->lastInsertId();
            $newArticle = $this->getById($insertId);
            if ($newArticle) {
                require_once __DIR__ . '/../config/functions.php';
                sendNewsEmailNotification($newArticle);
            }
        }
        
        return $result;
    }

    public function update($id, $data) {
        $existingNews = $this->getById($id);
        $wasNotPublished = $existingNews && $existingNews['status'] !== 'PUBLISHED';
        $isNowPublished = isset($data['status']) && $data['status'] === 'PUBLISHED';
        
        $set = array_map(fn($key) => "$key = :$key", array_keys($data));
        $sql = "UPDATE news SET " . implode(", ", $set) . " WHERE id = :id";
        $data['id'] = $id;
        $stmt = $this->db->getConnection()->prepare($sql);
        $result = $stmt->execute($data);
        
        // Send email notification if transitioning from draft/archived to published
        if ($result && $wasNotPublished && $isNowPublished) {
            $updatedNews = $this->getById($id);
            if ($updatedNews) {
                require_once __DIR__ . '/../config/functions.php';
                sendNewsEmailNotification($updatedNews);
            }
        }
        
        return $result;
    }

    public function delete($id) {
        $sql = "DELETE FROM news WHERE id = ?";
        return $this->db->execute($sql, [$id]);
    }

    public function getCategories() {
        $sql = "SELECT DISTINCT category FROM news WHERE status = 'PUBLISHED' ORDER BY category";
        return $this->db->fetchAll($sql);
    }
}
?>