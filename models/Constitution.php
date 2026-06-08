<?php
class Constitution {
    private $db;

    public function __construct($database) {
        $this->db = $database;
    }

    public function getActive() {
        return $this->db->fetch("SELECT * FROM constitution WHERE is_active = TRUE ORDER BY created_at DESC LIMIT 1");
    }

    public function getById($id) {
        return $this->db->fetch("SELECT * FROM constitution WHERE id = ?", [$id]);
    }

    public function getAll() {
        return $this->db->fetchAll("SELECT * FROM constitution ORDER BY created_at DESC");
    }

    public function create($data) {
        $sql = "INSERT INTO constitution (title, file_path, original_filename, file_size, uploaded_by, version, is_active) 
                VALUES (?, ?, ?, ?, ?, ?, ?)";
        $stmt = $this->db->getConnection()->prepare($sql);
        $stmt->execute([
            $data['title'],
            $data['file_path'],
            $data['original_filename'],
            $data['file_size'],
            $data['uploaded_by'],
            $data['version'] ?? '1.0',
            $data['is_active'] ?? true
        ]);
        return $this->db->getConnection()->lastInsertId();
    }

    public function update($id, $data) {
        $set = array_map(fn($key) => "$key = :$key", array_keys($data));
        $sql = "UPDATE constitution SET " . implode(", ", $set) . " WHERE id = :id";
        $params = $data;
        $params['id'] = $id;
        $stmt = $this->db->getConnection()->prepare($sql);
        return $stmt->execute($params);
    }

    public function setActive($id) {
        $this->db->execute("UPDATE constitution SET is_active = FALSE");
        return $this->db->execute("UPDATE constitution SET is_active = TRUE WHERE id = ?", [$id]);
    }

    public function delete($id) {
        return $this->db->execute("DELETE FROM constitution WHERE id = ?", [$id]);
    }

    public function count() {
        return $this->db->fetch("SELECT COUNT(*) as count FROM constitution")['count'];
    }
}
?>