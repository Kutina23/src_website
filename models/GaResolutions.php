<?php
class GaResolutions {
    private $db;

    public function __construct($database) {
        $this->db = $database;
    }

    public function getAll($filters = []) {
        $sql = "SELECT r.*,
                       s.title       AS session_title,
                       s.session_type,
                       uf.first_name AS proposer_first, uf.last_name AS proposer_last,
                       us.first_name AS seconded_first, us.last_name AS seconded_last
                FROM ga_resolutions r
                JOIN ga_sessions s ON r.session_id = s.id
                LEFT JOIN users uf ON r.proposer_id = uf.id
                LEFT JOIN users us ON r.seconded_by = us.id
                WHERE 1=1";

        $params = [];

        if (!empty($filters['session_id'])) {
            $sql .= " AND r.session_id = ?";
            $params[] = (int)$filters['session_id'];
        }
        if (!empty($filters['category'])) {
            $sql .= " AND r.category = ?";
            $params[] = $filters['category'];
        }
        if (!empty($filters['status'])) {
            $sql .= " AND r.status = ?";
            $params[] = $filters['status'];
        }
        if (!empty($filters['search'])) {
            $sql .= " AND (r.title LIKE ? OR r.resolution_no LIKE ? OR r.body LIKE ?)";
            $params[] = "%{$filters['search']}%";
            $params[] = "%{$filters['search']}%";
            $params[] = "%{$filters['search']}%";
        }

        $sql .= " ORDER BY r.created_at DESC";
        return $this->db->fetchAll($sql, $params);
    }

    public function getById($id) {
        return $this->db->fetch(
            "SELECT r.*,
                    s.title AS session_title, s.session_type,
                    uf.first_name AS proposer_first, uf.last_name AS proposer_last,
                    us.first_name AS seconded_first, us.last_name AS seconded_last
             FROM ga_resolutions r
             JOIN ga_sessions s ON r.session_id = s.id
             LEFT JOIN users uf ON r.proposer_id = uf.id
             LEFT JOIN users us ON r.seconded_by = us.id
             WHERE r.id = ?",
            [$id]
        );
    }

    public function getBySessionId($sessionId) {
        return $this->db->fetchAll(
            "SELECT r.*,
                    uf.first_name AS proposer_first, uf.last_name AS proposer_last
             FROM ga_resolutions r
             LEFT JOIN users uf ON r.proposer_id = uf.id
             WHERE r.session_id = ?
             ORDER BY r.resolution_no ASC",
            [$sessionId]
        );
    }

    public function getPassed($limit = null) {
        $sql = "SELECT r.*, s.title AS session_title, s.scheduled_datetime
                FROM ga_resolutions r
                JOIN ga_sessions s ON r.session_id = s.id
                WHERE r.status = 'PASSED'
                ORDER BY r.created_at DESC";
        if ($limit) $sql .= " LIMIT " . (int)$limit;
        return $this->db->fetchAll($sql);
    }

    public function getPending() {
        return $this->db->fetchAll(
            "SELECT r.*, s.title AS session_title
             FROM ga_resolutions r
             JOIN ga_sessions s ON r.session_id = s.id
             WHERE r.status = 'PENDING'
             ORDER BY r.created_at ASC"
        );
    }

    public function create($data) {
        return $this->db->insert("ga_resolutions", [
            "session_id"  => (int)$data["session_id"],
            "resolution_no"  => $data["resolution_no"],
            "title"         => $data["title"],
            "body"          => $data["body"],
            "category"      => $data["category"] ?? "RESOLUTION",
            "status"        => $data["status"] ?? "PENDING",
            "vote_for"      => (int)($data["vote_for"]    ?? 0),
            "vote_against"  => (int)($data["vote_against"] ?? 0),
            "vote_abstain"  => (int)($data["vote_abstain"] ?? 0),
            "proposer_id"   => !empty($data["proposer_id"]) ? (int)$data["proposer_id"] : null,
            "seconded_by"   => !empty($data["seconded_by"]) ? (int)$data["seconded_by"] : null,
        ]);
    }

    public function update($id, $data) {
        $set = [];
        if (isset($data["session_id"]))    $set["session_id"]    = (int)$data["session_id"];
        if (isset($data["resolution_no"])) $set["resolution_no"] = $data["resolution_no"];
        if (isset($data["title"]))         $set["title"]         = $data["title"];
        if (isset($data["body"]))          $set["body"]          = $data["body"];
        if (isset($data["category"]))      $set["category"]      = $data["category"];
        if (isset($data["status"]))        $set["status"]        = $data["status"];
        if (isset($data["vote_for"]))      $set["vote_for"]      = (int)$data["vote_for"];
        if (isset($data["vote_against"]))  $set["vote_against"]  = (int)$data["vote_against"];
        if (isset($data["vote_abstain"]))  $set["vote_abstain"]  = (int)$data["vote_abstain"];
        if (isset($data["proposer_id"]))   $set["proposer_id"]   = !empty($data["proposer_id"]) ? (int)$data["proposer_id"] : null;
        if (isset($data["seconded_by"]))   $set["seconded_by"]   = !empty($data["seconded_by"]) ? (int)$data["seconded_by"] : null;

        if (empty($set)) return false;

        logActivity("update_ga_resolution", $_SESSION["user_id"] ?? null, [
            "resolution_id" => $id,
            "title"         => $data["title"] ?? ""
        ]);
        return $this->db->update("ga_resolutions", $set, ["id" => $id]);
    }

    public function delete($id) {
        $row = $this->getById($id);
        $this->db->delete("ga_resolutions", "id = " . (int)$id);
        logActivity("delete_ga_resolution", $_SESSION["user_id"] ?? null, [
            "resolution_id" => $id,
            "title"         => $row["title"] ?? ""
        ]);
        return true;
    }

    public function recordVote($id, $voteFor, $voteAgainst, $voteAbstain) {
        return $this->db->update("ga_resolutions", [
            "vote_for"     => (int)$voteFor,
            "vote_against" => (int)$voteAgainst,
            "vote_abstain" => (int)$voteAbstain
        ], ["id" => $id]);
    }

    public function getStats() {
        $total = (int)$this->db->fetch("SELECT COUNT(*) AS cnt FROM ga_resolutions")['cnt'];
        $passed  = (int)$this->db->fetch("SELECT COUNT(*) AS cnt FROM ga_resolutions WHERE status='PASSED'")['cnt'];
        $pending = (int)$this->db->fetch("SELECT COUNT(*) AS cnt FROM ga_resolutions WHERE status='PENDING'")['cnt'];
        $rejected= (int)$this->db->fetch("SELECT COUNT(*) AS cnt FROM ga_resolutions WHERE status='REJECTED'")['cnt'];
        return compact('total', 'passed', 'pending', 'rejected');
    }

    public function getCategoryOptions() {
        return ['RESOLUTION', 'MOTION', 'AMENDMENT', 'DECLARATION'];
    }

    public function getAllUsers() {
        return $this->db->fetchAll(
            "SELECT id, first_name, last_name, email FROM users WHERE is_active = TRUE ORDER BY last_name ASC"
        );
    }
}
