<?php
class GaVoting {
    private $db;

    public function __construct($database) {
        $this->db = $database;
    }

    // ─── GA VOTING SESSIONS ──────────────────────────────────

    public function getAll($filters = []) {
        $sql = "SELECT v.*,
                       s.title       AS session_title,
                       s.session_type,
                       uo.first_name AS opened_by_first,  uo.last_name AS opened_by_last,
                       uc.first_name AS closed_by_first,  uc.last_name AS closed_by_last,
                       (SELECT COUNT(*) FROM ga_vote_records WHERE voting_id = v.id) AS total_votes_cast
                FROM ga_voting v
                JOIN ga_sessions s ON v.session_id = s.id
                LEFT JOIN users uo ON v.opened_by = uo.id
                LEFT JOIN users uc ON v.closed_by = uc.id
                WHERE 1=1";

        $params = [];

        if (!empty($filters['session_id'])) {
            $sql .= " AND v.session_id = ?";
            $params[] = (int)$filters['session_id'];
        }
        if (!empty($filters['status'])) {
            $sql .= " AND v.status = ?";
            $params[] = $filters['status'];
        }
        if (!empty($filters['result'])) {
            $sql .= " AND v.result = ?";
            $params[] = $filters['result'];
        }
        if (!empty($filters['search'])) {
            $sql .= " AND (v.title LIKE ? OR s.title LIKE ?)";
            $params[] = "%{$filters['search']}%";
            $params[] = "%{$filters['search']}%";
        }

        $sql .= " ORDER BY v.opened_at DESC";
        return $this->db->fetchAll($sql, $params);
    }

    public function getById($id) {
        return $this->db->fetch(
            "SELECT v.*,
                    s.title AS session_title, s.session_type, s.scheduled_datetime,
                    uo.first_name AS opened_by_first, uo.last_name AS opened_by_last,
                    uc.first_name AS closed_by_first, uc.last_name AS closed_by_last
             FROM ga_voting v
             JOIN ga_sessions s ON v.session_id = s.id
             LEFT JOIN users uo ON v.opened_by = uo.id
             LEFT JOIN users uc ON v.closed_by = uc.id
             WHERE v.id = ?",
            [$id]
        );
    }

    public function getOpen() {
        return $this->db->fetchAll(
            "SELECT v.*, s.title AS session_title, s.session_type
             FROM ga_voting v
             JOIN ga_sessions s ON v.session_id = s.id
             WHERE v.status = 'OPEN'
             ORDER BY v.opened_at DESC"
        );
    }

    public function getClosed() {
        return $this->db->fetchAll(
            "SELECT v.*, s.title AS session_title, s.session_type
             FROM ga_voting v
             JOIN ga_sessions s ON v.session_id = s.id
             WHERE v.status = 'CLOSED'
             ORDER BY v.closed_at DESC"
        );
    }

    public function create($data) {
        return $this->db->insert("ga_voting", [
            "session_id"     => (int)$data["session_id"],
            "title"          => $data["title"],
            "description"    => $data["description"] ?? null,
            "vote_type"      => $data["vote_type"] ?? "SIMPLE_MAJORITY",
            "status"         => $data["status"] ?? "OPEN",
            "total_eligible" => (int)($data["total_eligible"] ?? 0),
            "opened_by"      => !empty($data["opened_by"]) ? (int)$data["opened_by"] : null,
            "opened_at"      => !empty($data["opened_at"]) ? $data["opened_at"] : date("Y-m-d H:i:s"),
        ]);
    }

    public function update($id, $data) {
        $set = [
            "session_id"     => (int)$data["session_id"],
            "title"          => $data["title"],
            "description"    => $data["description"] ?? null,
            "vote_type"      => $data["vote_type"] ?? "SIMPLE_MAJORITY",
            "status"         => $data["status"],
            "total_eligible" => (int)($data["total_eligible"] ?? 0),
        ];
        logActivity("update_ga_voting", $_SESSION["user_id"] ?? null, ["voting_id" => $id, "title" => $data["title"]]);
        return $this->db->update("ga_voting", $set, ["id" => $id]);
    }

    public function delete($id) {
        $row = $this->getById($id);
        $this->db->execute("DELETE FROM ga_vote_records WHERE voting_id = ?", [$id]);
        $this->db->delete("ga_voting", "id = " . (int)$id);
        logActivity("delete_ga_voting", $_SESSION["user_id"] ?? null, [
            "voting_id" => $id,
            "title"     => $row["title"] ?? ""
        ]);
        return true;
    }

    // ─── OPEN / CLOSE ──────────────────────────────────────

    public function openVoting($id, $userId) {
        return $this->db->update("ga_voting", [
            "status"   => "OPEN",
            "opened_by" => $userId,
            "opened_at" => date("Y-m-d H:i:s"),
        ], ["id" => $id]);
    }

    public function closeVoting($id, $userId) {
        $this->recalcVotes($id);

        // Read live approved counts so result is always accurate
        $counts = $this->getApprovedCounts($id);
        $result = match(true) {
            $counts['yes'] > 0 && $counts['no'] === 0 => 'PASSED',
            $counts['yes'] > $counts['no']             => 'PASSED',
            $counts['yes'] === 0 && $counts['no'] > 0  => 'REJECTED',
            default                                     => 'REJECTED',
        };

        return $this->db->update("ga_voting", [
            "status"     => "CLOSED",
            "closed_by"  => $userId,
            "closed_at"  => date("Y-m-d H:i:s"),
            "result"     => $result
        ], ["id" => $id]);
    }

    private function calculateResult($votingId) {
        $voting = $this->getById($votingId);
        if (!$voting) return false;
        $type = $voting['vote_type'] ?? 'SIMPLE_MAJORITY';

        // Read live approved counts — avoids stale ga_voting cached columns
        $c = $this->getApprovedCounts($votingId);
        $yes = $c['yes'];
        $no  = $c['no'];

        if ($type === 'UNANIMOUS') {
            return $no === 0 && $yes > 0;
        }
        if ($type === 'TWO_THIRDS') {
            return ($yes / max(1, $yes + $no)) >= 0.667;
        }
        return $yes > $no;
    }

    public function recalcVotes($votingId) {
        $rows = $this->db->fetchAll(
            "SELECT choice, COUNT(*) AS cnt
             FROM ga_vote_records
             WHERE voting_id = ? AND is_approved = 'yes'
             GROUP BY choice",
            [$votingId]
        );
        $counts = ['YES' => 0, 'NO' => 0, 'ABSTAIN' => 0];
        foreach ($rows as $r) { $counts[strtoupper($r['choice'])] = (int)$r['cnt']; }
        $total = array_sum($counts);

        $this->db->query(
            "UPDATE ga_voting SET vote_yes = ?, vote_no = ?, vote_abstain = ?, total_voted = ? WHERE id = ?",
            [$counts['YES'], $counts['NO'], $counts['ABSTAIN'], $total, $votingId]
        );
    }

    // ─── INDIVIDUAL VOTES ───────────────────────────────────

    public function vote($votingId, $userId, $choice) {
        $this->db->upsert(
            "ga_vote_records",
            ["voting_id" => $votingId, "user_id" => $userId],
            ["choice" => $choice, "voted_at" => date("Y-m-d H:i:s")]
        );
        $this->recalcVotes($votingId);
    }

    public function getVoteRecords($votingId) {
        return $this->db->fetchAll(
            "SELECT vr.*, u.first_name, u.last_name, r.name AS role
             FROM ga_vote_records vr
             JOIN users u ON vr.user_id = u.id
             LEFT JOIN roles r ON u.role_id = r.id
             WHERE vr.voting_id = ?
             ORDER BY r.priority ASC, u.last_name ASC",
            [$votingId]
        );
    }

    public function hasVoted($votingId, $userId) {
        $row = $this->db->fetch(
            "SELECT choice, is_approved FROM ga_vote_records WHERE voting_id = ? AND user_id = ?",
            [$votingId, $userId]
        );
        return $row ? ['choice' => $row['choice'], 'is_approved' => $row['is_approved']] : null;
    }

    public function hasDeviceVoted($votingId, $deviceId) {
        $row = $this->db->fetch(
            "SELECT choice, is_approved FROM ga_vote_records WHERE voting_id = ? AND device_id = ?",
            [$votingId, $deviceId]
        );
        return $row ? ['choice' => $row['choice'], 'is_approved' => $row['is_approved']] : null;
    }

    public function getPendingVotes($votingId) {
        return $this->db->fetchAll(
            "SELECT vr.*, u.first_name, u.last_name, r.name AS role
             FROM ga_vote_records vr
             JOIN users u ON vr.user_id = u.id
             LEFT JOIN roles r ON u.role_id = r.id
             WHERE vr.voting_id = ? AND vr.is_approved = 'pending'
             ORDER BY r.priority ASC, u.last_name ASC",
            [$votingId]
        );
    }

    public function approveVote($recordId) {
        return $this->db->update("ga_vote_records", ["is_approved" => "yes"], ["id" => $recordId]);
    }

    public function rejectVote($recordId) {
        return $this->db->update("ga_vote_records", ["is_approved" => "no"], ["id" => $recordId]);
    }

    /**
     * Returns approved-only vote counts for a single voting session.
     * Does not rely on cached ga_voting columns — no recalcVotes dependency.
     * @return array{yes:int,no:int,abstain:int,total:int}
     */
    public function getApprovedCounts($votingId) {
        $row = $this->db->fetch(
            "SELECT
               (SELECT COUNT(*) FROM ga_vote_records WHERE voting_id=? AND is_approved='yes' AND choice='YES')      AS yes,
               (SELECT COUNT(*) FROM ga_vote_records WHERE voting_id=? AND is_approved='yes' AND choice='NO')       AS no,
               (SELECT COUNT(*) FROM ga_vote_records WHERE voting_id=? AND is_approved='yes' AND choice='ABSTAIN')  AS abstain,
               (SELECT COUNT(*) FROM ga_vote_records WHERE voting_id=? AND is_approved='yes')                       AS total",
            [$votingId, $votingId, $votingId, $votingId]
        );
        return [
            'yes'     => (int)($row['yes']     ?? 0),
            'no'      => (int)($row['no']      ?? 0),
            'abstain' => (int)($row['abstain'] ?? 0),
            'total'   => (int)($row['total']   ?? 0),
        ];
    }

    public function getVoteTypeOptions() {
        return ['SIMPLE_MAJORITY', 'TWO_THIRDS', 'UNANIMOUS'];
    }

    // ─── STATS ───────────────────────────────────────────────

    public function getAllUsers() {
        return $this->db->fetchAll(
            "SELECT id, first_name, last_name, email FROM users WHERE is_active = TRUE ORDER BY last_name ASC"
        );
    }

    public function getStats() {
        $total      = (int)$this->db->fetch("SELECT COUNT(*) AS cnt FROM ga_voting")['cnt'];
        $open       = (int)$this->db->fetch("SELECT COUNT(*) AS cnt FROM ga_voting WHERE status='OPEN'")['cnt'];
        $closed     = (int)$this->db->fetch("SELECT COUNT(*) AS cnt FROM ga_voting WHERE status='CLOSED'")['cnt'];
        $passed     = (int)$this->db->fetch("SELECT COUNT(*) AS cnt FROM ga_voting WHERE result='PASSED'")['cnt'];
        $rejected   = (int)$this->db->fetch("SELECT COUNT(*) AS cnt FROM ga_voting WHERE result='REJECTED'")['cnt'];
        return compact('total', 'open', 'closed', 'passed', 'rejected');
    }
}
