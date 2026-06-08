<?php
class GaSessions {
    private $db;

    public function __construct($database) {
        $this->db = $database;
    }

    // ─────────────────────────────────────────────────────────
    // GA SESSIONS
    // ─────────────────────────────────────────────────────────

    public function getAll($filters = []) {
        $sql = "SELECT s.*,
                       (SELECT COUNT(*) FROM ga_attendance WHERE session_id = s.id AND attended = 1) as attendees_count,
                       (SELECT COUNT(*) FROM ga_attendance WHERE session_id = s.id) as invited_count
                FROM ga_sessions s
                WHERE 1=1";

        $params = [];

        if (!empty($filters['session_type'])) {
            $sql .= " AND s.session_type = ?";
            $params[] = $filters['session_type'];
        }

        if (!empty($filters['status'])) {
            $sql .= " AND s.status = ?";
            $params[] = $filters['status'];
        }

        if (!empty($filters['search'])) {
            $sql .= " AND (s.title LIKE ? OR s.description LIKE ?)";
            $params[] = "%{$filters['search']}%";
            $params[] = "%{$filters['search']}%";
        }

        $sql .= " ORDER BY s.scheduled_datetime DESC";
        return $this->db->fetchAll($sql, $params);
    }

    public function getById($id) {
        return $this->db->fetch(
            "SELECT s.*,
                    (SELECT COUNT(*) FROM ga_attendance WHERE session_id = s.id AND attended = 1) as attendees_count,
                    (SELECT COUNT(*) FROM ga_attendance WHERE session_id = s.id) as invited_count
             FROM ga_sessions s WHERE s.id = ?",
            [$id]
        );
    }

    public function getByType($type, int $limit = 0): array {
        $sql = "SELECT s.*,
                       (SELECT COUNT(*) FROM ga_attendance WHERE session_id = s.id AND attended = 1) as attendees_count,
                       (SELECT COUNT(*) FROM ga_attendance WHERE session_id = s.id) as invited_count,
                       (SELECT meeting_title FROM ga_minutes WHERE session_id = s.id ORDER BY uploaded_at DESC LIMIT 1) as minutes_meeting_title,
                       (SELECT status FROM ga_minutes WHERE session_id = s.id ORDER BY uploaded_at DESC LIMIT 1) as minutes_status,
                       (SELECT file_path FROM ga_minutes WHERE session_id = s.id ORDER BY uploaded_at DESC LIMIT 1) as minutes_file_path
                FROM ga_sessions s
                WHERE s.session_type = ?
                ORDER BY s.scheduled_datetime DESC";
        if ($limit > 0) $sql .= " LIMIT " . (int)$limit;
        return $this->db->fetchAll($sql, [$type]);
    }

    public function getUpcoming() {
        return $this->db->fetchAll(
            "SELECT s.* FROM ga_sessions s
             WHERE s.status IN ('SCHEDULED','IN_PROGRESS') AND s.scheduled_datetime >= NOW()
             ORDER BY s.scheduled_datetime ASC LIMIT 5"
        );
    }

    public function getPast() {
        return $this->db->fetchAll(
            "SELECT s.*,
                    (SELECT COUNT(*) FROM ga_attendance WHERE session_id = s.id AND attended = 1) as attendees_count
             FROM ga_sessions s
             WHERE s.status IN ('COMPLETED','CANCELLED')
             ORDER BY s.scheduled_datetime DESC"
        );
    }

    public function create($data) {
        $id = $this->db->insert("ga_sessions", [
            "session_type"  => $data["session_type"],
            "title"         => $data["title"],
            "description"   => $data["description"] ?? null,
            "scheduled_datetime" => $data["scheduled_datetime"] ?: null,
            "location"       => $data["location"] ?? null,
            "status"         => $data["status"] ?? "SCHEDULED",
            "minutes_url"    => $data["minutes_url"] ?? null
        ]);
        logActivity("create_ga_session", $_SESSION["user_id"] ?? null, [
            "session_id" => $id,
            "title"      => $data["title"],
            "type"       => $data["session_type"]
        ]);
        
        // Send email notification for scheduled GA sessions
        if ($data["status"] === "SCHEDULED" && !empty($data["scheduled_datetime"])) {
            $gaSession = $this->getById($id);
            if ($gaSession) {
                require_once __DIR__ . '/../config/functions.php';
                sendGaSessionEmailNotification($gaSession);
            }
        }
        
        return $id;
    }

    public function update($id, $data) {
        $existingSession = $this->getById($id);
        $wasNotScheduled = $existingSession && $existingSession['status'] !== 'SCHEDULED';
        $isNowScheduled = isset($data['status']) && $data['status'] === 'SCHEDULED';
        
        $updateData = [
            "session_type"       => $data["session_type"],
            "title"              => $data["title"],
            "description"        => $data["description"] ?? null,
            "scheduled_datetime" => $data["scheduled_datetime"] ?: null,
            "location"           => $data["location"] ?? null,
            "status"             => $data["status"],
            "minutes_url"        => $data["minutes_url"] ?? null
        ];
        $result = $this->db->update("ga_sessions", $updateData, ["id" => $id]);
        logActivity("update_ga_session", $_SESSION["user_id"] ?? null, [
            "session_id" => $id,
            "title"      => $data["title"]
        ]);
        
        // Send email notification if transitioning to scheduled status
        if ($result && $wasNotScheduled && $isNowScheduled && !empty($data["scheduled_datetime"])) {
            $updatedSession = $this->getById($id);
            if ($updatedSession) {
                require_once __DIR__ . '/../config/functions.php';
                sendGaSessionEmailNotification($updatedSession);
            }
        }
        
        return $result;
    }

    public function delete($id) {
        $session = $this->getById($id);
        $this->db->execute("DELETE FROM ga_attendance WHERE session_id = ?", [$id]);
        $result = $this->db->delete("ga_sessions", "id = " . (int)$id);
        logActivity("delete_ga_session", $_SESSION["user_id"] ?? null, [
            "session_id" => $id,
            "title"      => $session["title"] ?? ""
        ]);
        return $result;
    }

    public function countByStatus($status) {
        return (int)$this->db->fetch(
            "SELECT COUNT(*) as cnt FROM ga_sessions WHERE status = ?",
            [$status]
        )['cnt'];
    }

    public function getStats() {
        return [
            "total"        => $this->countTotal(),
            "scheduled"    => $this->countByStatus("SCHEDULED"),
            "in_progress"  => $this->countByStatus("IN_PROGRESS"),
            "completed"    => $this->countByStatus("COMPLETED"),
            "cancelled"    => $this->countByStatus("CANCELLED"),
            "annual"       => (int)$this->db->fetch("SELECT COUNT(*) as cnt FROM ga_sessions WHERE session_type = 'ANNUAL'")['cnt'],
            "emergency"    => (int)$this->db->fetch("SELECT COUNT(*) as cnt FROM ga_sessions WHERE session_type = 'EMERGENCY'")['cnt'],
            "special"      => (int)$this->db->fetch("SELECT COUNT(*) as cnt FROM ga_sessions WHERE session_type = 'SPECIAL'")['cnt']
        ];
    }

    private function countTotal() {
        return (int)$this->db->fetch("SELECT COUNT(*) as cnt FROM ga_sessions")['cnt'];
    }

    // ─────────────────────────────────────────────────────────
    // GA ATTENDANCE
    // ─────────────────────────────────────────────────────────

    public function getAttendanceBySession($sessionId) {
        return $this->db->fetchAll(
            "SELECT a.session_id, a.user_id, a.attended,
                    u.first_name, u.last_name, u.email,
                    r.name as role
             FROM ga_attendance a
             JOIN users u ON a.user_id = u.id
             LEFT JOIN roles r ON u.role_id = r.id
             WHERE a.session_id = ?
             ORDER BY r.priority ASC, u.last_name ASC",
            [$sessionId]
        );
    }

    public function getAllUsersForAttendance() {
        return $this->db->fetchAll(
            "SELECT u.id, u.first_name, u.last_name, u.email,
                    r.name as role, r.priority as role_priority,
                    d.name as department
             FROM users u
             JOIN roles r ON u.role_id = r.id
             LEFT JOIN departments d ON u.department_id = d.id
             WHERE u.is_active = TRUE
             ORDER BY r.priority ASC, u.last_name ASC"
        );
    }

    public function markAttendance($sessionId, $userId, $attended = 1) {
        return $this->db->upsert(
            "ga_attendance",
            ["session_id" => $sessionId, "user_id" => $userId],
            ["attended" => $attended ? 1 : 0]
        );
    }

    public function markPresent($sessionId, $userId) {
        $this->markAttendance($sessionId, $userId, true);
    }

    public function markAbsent($sessionId, $userId) {
        $this->markAttendance($sessionId, $userId, false);
    }

    public function bulkMarkAttendance($sessionId, $attended, $absent = []) {
        $allUsers = $this->getAllUsersForAttendance();
        foreach ($allUsers as $user) {
            if (in_array($user['id'], $absent)) {
                $this->markAbsent($sessionId, $user['id']);
            } else {
                $this->markPresent($sessionId, $user['id']);
            }
        }
    }

    public function getAttendanceStats($sessionId) {
        $totalInvited  = $this->db->fetch(
            "SELECT COUNT(DISTINCT user_id) as total FROM ga_attendance WHERE session_id = ?",
            [$sessionId]
        )['total'];

        $presentCount  = (int)$this->db->fetch(
            "SELECT COUNT(*) as cnt FROM ga_attendance WHERE session_id = ? AND attended = 1",
            [$sessionId]
        )['cnt'];

        $absentCount  = (int)$this->db->fetch(
            "SELECT COUNT(*) as cnt FROM ga_attendance WHERE session_id = ? AND attended = 0",
            [$sessionId]
        )['cnt'];

        $totalQuota = $this->getTotalUserCount();
        $quorumMet = ($totalQuota > 0) ? (($presentCount / $totalQuota) >= 0.25) : false;

        return [
            "total_invited" => $totalInvited ?: $totalQuota,
            "present"       => $presentCount,
            "absent"        => $absentCount,
            "total_users"   => $totalQuota,
            "quorum_met"    => $quorumMet,
            "attendance_pct" => $totalQuota > 0 ? round(($presentCount / $totalQuota) * 100, 1) : 0
        ];
    }

    private function getTotalUserCount() {
        return (int)$this->db->fetch(
            "SELECT COUNT(*) as cnt FROM users WHERE is_active = TRUE"
        )['cnt'];
    }

    public function hasAttendance($sessionId, $userId) {
        $row = $this->db->fetch(
            "SELECT attended FROM ga_attendance WHERE session_id = ? AND user_id = ?",
            [$sessionId, $userId]
        );
        return $row ? (bool)$row['attended'] : null;
    }

    public function getSessionTypes() {
        return ['ANNUAL', 'EMERGENCY', 'SPECIAL'];
    }

    public function getStatusOptions() {
        return ['SCHEDULED', 'IN_PROGRESS', 'COMPLETED', 'CANCELLED'];
    }

    // ─── PUBLIC PAGE HELPERS ─────────────────────────────────

    /**
     * Resolved count of rows per session for the carded "Nth AGM/EGA/…" label.
     */
    public function getOrdinalTitle(int $year, string $type): string {
        $typeLabel = match($type) {
            'ANNUAL'    => 'AGM',
            'EMERGENCY' => 'EGA',
            'SPECIAL'   => 'Special Session',
        };
        return $typeLabel . ' ' . ordinalSuffix($year);
    }

    /**
     * Public: fetches the next upcoming ANNUAL session that has an agenda PDF URL.
     * Used by council.php to drive the Agenda preview.
     */
    public function getUpcomingSessionForAgenda() {
        return $this->db->fetch(
            "SELECT s.*
               FROM ga_sessions s
              WHERE s.session_type = 'ANNUAL'
                AND s.status       IN ('SCHEDULED','IN_PROGRESS')
                AND s.minutes_url  IS NOT NULL
                AND s.minutes_url  <> ''
              ORDER BY s.scheduled_datetime ASC
              LIMIT 1"
        ) ?: $this->db->fetch(
            "SELECT s.*
               FROM ga_sessions s
              WHERE s.session_type = 'ANNUAL'
                AND s.minutes_url IS NOT NULL
                AND s.minutes_url <> ''
              ORDER BY s.scheduled_datetime DESC
              LIMIT 1"
        );
    }

    /**
     * Public: recently passed resolutions (status = PASSED) for the archive page.
     */
    public function getResolutionsForPage(array $filters = []): array {
        $sql = "SELECT r.*,
                       s.title AS session_title,
                       s.session_type,
                       s.scheduled_datetime,
                       uf.first_name AS proposer_first, uf.last_name AS proposer_last
                FROM ga_resolutions r
                JOIN ga_sessions s   ON r.session_id  = s.id
                LEFT JOIN users uf   ON r.proposer_id  = uf.id
                WHERE r.status = 'PASSED'";
        $params = [];
        if (!empty($filters['session_type'])) {
            $sql .= " AND s.session_type = ?";
            $params[] = $filters['session_type'];
        }
        if (!empty($filters['search'])) {
            $sql .= " AND (r.title LIKE ? OR r.resolution_no LIKE ?)";
            $params[] = "%{$filters['search']}%";
            $params[] = "%{$filters['search']}%";
        }
        $sql .= " ORDER BY r.created_at DESC LIMIT 50";
        return $this->db->fetchAll($sql, $params);
    }

    /**
     * Public: closed/resolved voting records for the archive page.
     */
    public function getVotingRecordsForPage(array $filters = [], int $limit = 50): array {
        $sql = "SELECT v.*,
                        s.title AS session_title,
                        s.session_type,
                        s.scheduled_datetime,
                        (SELECT COUNT(*) FROM ga_vote_records WHERE voting_id = v.id) AS total_voted
                 FROM ga_voting v
                 JOIN ga_sessions s ON v.session_id = s.id
                 WHERE v.status IN ('CLOSED','OPEN')";
        $params = [];
        if (!empty($filters['session_type'])) {
            $sql .= " AND s.session_type = ?";
            $params[] = $filters['session_type'];
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
        $sql .= " ORDER BY v.closed_at DESC LIMIT " . (int)$limit;
        return $this->db->fetchAll($sql, $params);
    }

    /**
     * Public: meeting minutes with session join and optional filter.
     * Falls back to ga_minutes alone if ga_sessions link is absent.
     */
    public function getMinutesForPage(array $filters = []): array {
        $sql = "SELECT m.*,
                       s.title        AS session_title,
                       s.session_type,
                       s.scheduled_datetime
                FROM ga_minutes m
                LEFT JOIN ga_sessions s  ON m.session_id  = s.id
                WHERE m.status IN ('PUBLISHED', 'RATIFIED')";
        $params = [];
        if (!empty($filters['session_type'])) {
            $sql .= " AND s.session_type = ?";
            $params[] = $filters['session_type'];
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
        $sql .= " ORDER BY m.uploaded_at DESC LIMIT 50";
        return $this->db->fetchAll($sql, $params);
    }
}
