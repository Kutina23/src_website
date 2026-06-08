<?php
class GaMinutes {
    private $db;

    public function __construct($database) {
        $this->db = $database;
    }

    public function getAll($filters = []) {
        $sql = "SELECT m.*,
                       u.first_name  AS uploaded_by_first,
                       u.last_name   AS uploaded_by_last,
                       s.title       AS session_title,
                       s.session_type
                FROM ga_minutes m
                JOIN ga_sessions s ON m.session_id = s.id
                LEFT JOIN users u ON m.uploaded_by = u.id
                WHERE 1=1";

        $params = [];

        if (!empty($filters['session_id'])) {
            $sql .= " AND m.session_id = ?";
            $params[] = (int)$filters['session_id'];
        }
        if (!empty($filters['status'])) {
            $sql .= " AND m.status = ?";
            $params[] = $filters['status'];
        }
        if (!empty($filters['search'])) {
            $sql .= " AND (m.meeting_title LIKE ? OR s.title LIKE ?)";
            $params[] = "%{$filters['search']}%";
            $params[] = "%{$filters['search']}%";
        }

        $sql .= " ORDER BY m.uploaded_at DESC";
        return $this->db->fetchAll($sql, $params);
    }

    public function getById($id) {
        return $this->db->fetch(
            "SELECT m.*,
                    u.first_name AS uploaded_by_first, u.last_name AS uploaded_by_last,
                    s.title AS session_title, s.session_type, s.scheduled_datetime
             FROM ga_minutes m
             JOIN ga_sessions s ON m.session_id = s.id
             LEFT JOIN users u ON m.uploaded_by = u.id
             WHERE m.id = ?",
            [$id]
        );
    }

    public function getBySessionId($sessionId) {
        return $this->db->fetchAll(
            "SELECT m.*,
                    u.first_name AS uploaded_by_first, u.last_name AS uploaded_by_last
             FROM ga_minutes m
             LEFT JOIN users u ON m.uploaded_by = u.id
             WHERE m.session_id = ?
             ORDER BY m.uploaded_at DESC",
            [$sessionId]
        );
    }

    public function getLatest($limit = 10) {
        return $this->db->fetchAll(
            "SELECT m.*, s.title AS session_title, s.session_type
             FROM ga_minutes m
             JOIN ga_sessions s ON m.session_id = s.id
             ORDER BY m.uploaded_at DESC
             LIMIT ?",
            [$limit]
        );
    }

    public function create($data) {
        return $this->db->insert("ga_minutes", [
            "session_id"    => (int)$data["session_id"],
            "meeting_title" => $data["meeting_title"],
            "file_path"     => $data["file_path"],
            "original_name" => $data["original_name"],
            "file_size"     => (int)($data["file_size"] ?? 0),
            "mime_type"     => $data["mime_type"] ?? "application/pdf",
            "description"   => $data["description"] ?? null,
            "status"        => $data["status"] ?? "DRAFT",
            "uploaded_by"   => !empty($data["uploaded_by"]) ? (int)$data["uploaded_by"] : null
        ]);
    }

    public function update($id, $data) {
        $update = [];

        if (array_key_exists("session_id", $data)) {
            $update["session_id"] = (int)$data["session_id"];
        }
        if (array_key_exists("meeting_title", $data)) {
            $update["meeting_title"] = $data["meeting_title"];
        }
        if (array_key_exists("description", $data)) {
            $update["description"] = $data["description"] ?? null;
        }
        if (array_key_exists("status", $data)) {
            $update["status"] = $data["status"];
        }
        if (array_key_exists("uploaded_by", $data)) {
            $update["uploaded_by"] = !empty($data["uploaded_by"]) ? (int)$data["uploaded_by"] : null;
        }

        if (!empty($data["file_path"])) {
            $update["file_path"]     = $data["file_path"];
            $update["original_name"] = $data["original_name"];
            $update["file_size"]     = (int)($data["file_size"] ?? 0);
            $update["mime_type"]     = $data["mime_type"] ?? "application/pdf";
        }

        if (empty($update)) {
            logActivity("update_ga_minutes_noop", $_SESSION["user_id"] ?? null, ["minutes_id" => $id]);
            return true;
        }

        $this->db->update("ga_minutes", $update, ["id" => $id]);
        logActivity("update_ga_minutes", $_SESSION["user_id"] ?? null, ["minutes_id" => $id]);
        return true;
    }

    public function delete($id) {
        $row = $this->getById($id);
        $this->db->delete("ga_minutes", "id = " . (int)$id);
        logActivity("delete_ga_minutes", $_SESSION["user_id"] ?? null, [
            "minutes_id" => $id,
            "meeting"    => $row["meeting_title"] ?? ""
        ]);
        return true;
    }

    public function countByStatus($status) {
        return (int)$this->db->fetch(
            "SELECT COUNT(*) AS cnt FROM ga_minutes WHERE status = ?",
            [$status]
        )['cnt'];
    }

    public function getStats() {
        $total = (int)$this->db->fetch("SELECT COUNT(*) AS cnt FROM ga_minutes")['cnt'];
        return [
            "total"    => $total,
            "draft"    => $this->countByStatus('DRAFT'),
            "published"=> $this->countByStatus('PUBLISHED'),
            "ratified" => $this->countByStatus('RATIFIED'),
            "archived" => $this->countByStatus('ARCHIVED'),
        ];
    }

    public function getStatusOptions() {
        return ['DRAFT', 'PUBLISHED', 'RATIFIED', 'ARCHIVED'];
    }
}
