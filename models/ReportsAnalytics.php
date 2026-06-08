<?php
class ReportsAnalytics {
    private $db;

    public function __construct($database) {
        $this->db = $database;
    }

    // ── KPI Totals ────────────────────────────────────────────

    public function getUserTotals(): array {
        $roles = ['PRO', 'PRESIDENT', 'DIRECTOR ICT', 'DEAN', 'STUDENT'];
        $result = [];
        foreach ($roles as $role) {
            $result[$role] = (int)$this->db->fetch(
                "SELECT COUNT(*) AS cnt
                   FROM users u
                   JOIN roles r ON r.id = u.role_id
                  WHERE r.name = ? AND u.is_active = 1",
                [$role]
            )['cnt'];
        }
        $result['total'] = array_sum($result);
        return $result;
    }

    public function getComplaintStats(): array {
        return [
            'total'       => (int)$this->db->fetch("SELECT COUNT(*) AS cnt FROM complaints")['cnt'],
            'open'        => (int)$this->db->fetch("SELECT COUNT(*) AS cnt FROM complaints WHERE status = 'OPEN'")['cnt'],
            'in_progress' => (int)$this->db->fetch("SELECT COUNT(*) AS cnt FROM complaints WHERE status = 'IN_PROGRESS'")['cnt'],
            'resolved'    => (int)$this->db->fetch("SELECT COUNT(*) AS cnt FROM complaints WHERE status = 'RESOLVED'")['cnt'],
            'closed'      => (int)$this->db->fetch("SELECT COUNT(*) AS cnt FROM complaints WHERE status = 'CLOSED'")['cnt'],
        ];
    }

    public function getDocumentStats(): array {
        return [
            'total'     => (int)$this->db->fetch("SELECT COUNT(*) AS cnt FROM document_requests")['cnt'],
            'pending'   => (int)$this->db->fetch("SELECT COUNT(*) AS cnt FROM document_requests WHERE status = 'PENDING'")['cnt'],
            'processing'=> (int)$this->db->fetch("SELECT COUNT(*) AS cnt FROM document_requests WHERE status = 'PROCESSING'")['cnt'],
            'ready'     => (int)$this->db->fetch("SELECT COUNT(*) AS cnt FROM document_requests WHERE status = 'READY'")['cnt'],
            'collected' => (int)$this->db->fetch("SELECT COUNT(*) AS cnt FROM document_requests WHERE status = 'COLLECTED'")['cnt'],
        ];
    }

    public function getClubStats(): array {
        return [
            'total'     => (int)$this->db->fetch("SELECT COUNT(*) AS cnt FROM clubs")['cnt'],
            'active'    => (int)$this->db->fetch("SELECT COUNT(*) AS cnt FROM clubs WHERE status = 'ACTIVE'")['cnt'],
            'inactive'  => (int)$this->db->fetch("SELECT COUNT(*) AS cnt FROM clubs WHERE status = 'INACTIVE'")['cnt'],
            'suspended' => (int)$this->db->fetch("SELECT COUNT(*) AS cnt FROM clubs WHERE status = 'SUSPENDED'")['cnt'],
        ];
    }

    public function getGaStats(): array {
        return [
            'total'      => (int)$this->db->fetch("SELECT COUNT(*) AS cnt FROM ga_sessions")['cnt'],
            'scheduled'  => (int)$this->db->fetch("SELECT COUNT(*) AS cnt FROM ga_sessions WHERE status = 'SCHEDULED'")['cnt'],
            'in_progress'=> (int)$this->db->fetch("SELECT COUNT(*) AS cnt FROM ga_sessions WHERE status = 'IN_PROGRESS'")['cnt'],
            'completed'  => (int)$this->db->fetch("SELECT COUNT(*) AS cnt FROM ga_sessions WHERE status = 'COMPLETED'")['cnt'],
            'cancelled'  => (int)$this->db->fetch("SELECT COUNT(*) AS cnt FROM ga_sessions WHERE status = 'CANCELLED'")['cnt'],
            'annual'     => (int)$this->db->fetch("SELECT COUNT(*) AS cnt FROM ga_sessions WHERE session_type = 'ANNUAL'")['cnt'],
            'emergency'  => (int)$this->db->fetch("SELECT COUNT(*) AS cnt FROM ga_sessions WHERE session_type = 'EMERGENCY'")['cnt'],
            'special'    => (int)$this->db->fetch("SELECT COUNT(*) AS cnt FROM ga_sessions WHERE session_type = 'SPECIAL'")['cnt'],
        ];
    }

    public function getVotingStats(): array {
        return [
            'total'     => (int)$this->db->fetch("SELECT COUNT(*) AS cnt FROM ga_voting")['cnt'],
            'open'      => (int)$this->db->fetch("SELECT COUNT(*) AS cnt FROM ga_voting WHERE status = 'OPEN'")['cnt'],
            'closed'    => (int)$this->db->fetch("SELECT COUNT(*) AS cnt FROM ga_voting WHERE status = 'CLOSED'")['cnt'],
            'passed'    => (int)$this->db->fetch("SELECT COUNT(*) AS cnt FROM ga_voting WHERE result = 'PASSED'")['cnt'],
            'rejected'  => (int)$this->db->fetch("SELECT COUNT(*) AS cnt FROM ga_voting WHERE result = 'REJECTED'")['cnt'],
        ];
    }

    public function getResolutionStats(): array {
        $rows = $this->db->fetchAll(
            "SELECT status, COUNT(*) AS cnt
               FROM ga_resolutions
           GROUP BY status"
        );
        $out = ['total' => (int)$this->db->fetch("SELECT COUNT(*) AS cnt FROM ga_resolutions")['cnt']];
        foreach ($rows as $row) {
            $out[$row['status']] = (int)$row['cnt'];
        }
        return $out;
    }

    public function getNewsStats(): array {
        return [
            'total'     => (int)$this->db->fetch("SELECT COUNT(*) AS cnt FROM news")['cnt'],
            'published' => (int)$this->db->fetch("SELECT COUNT(*) AS cnt FROM news WHERE status = 'PUBLISHED'")['cnt'],
            'draft'     => (int)$this->db->fetch("SELECT COUNT(*) AS cnt FROM news WHERE status = 'DRAFT'")['cnt'],
            'archived'  => (int)$this->db->fetch("SELECT COUNT(*) AS cnt FROM news WHERE status = 'ARCHIVED'")['cnt'],
        ];
    }

    public function getAuditStats(): array {
        $total = (int)$this->db->fetch("SELECT COUNT(*) AS cnt FROM audit_logs")['cnt'];
        $today = (int)$this->db->fetch(
            "SELECT COUNT(*) AS cnt FROM audit_logs WHERE DATE(created_at) = CURDATE()"
        )['cnt'];
        $thisWeek = (int)$this->db->fetch(
            "SELECT COUNT(*) AS cnt FROM audit_logs WHERE YEARWEEK(created_at, 1) = YEARWEEK(NOW(), 1)"
        )['cnt'];
        $thisMonth = (int)$this->db->fetch(
            "SELECT COUNT(*) AS cnt FROM audit_logs WHERE MONTH(created_at) = MONTH(NOW()) AND YEAR(created_at) = YEAR(NOW())"
        )['cnt'];
        return ['total' => $total, 'today' => $today, 'this_week' => $thisWeek, 'this_month' => $thisMonth];
    }

    public function getCommitteeStats(): array {
        return [
            'total'    => (int)$this->db->fetch("SELECT COUNT(*) AS cnt FROM committees WHERE is_active = 1")['cnt'],
            'inactive' => (int)$this->db->fetch("SELECT COUNT(*) AS cnt FROM committees WHERE is_active = 0")['cnt'],
            'members'  => (int)$this->db->fetch("SELECT COUNT(*) AS cnt FROM committee_members")['cnt'],
        ];
    }

    // ── Breakdown Charts ──────────────────────────────────────

    public function getComplaintsByCategory(): array {
        return $this->db->fetchAll(
            "SELECT category, COUNT(*) AS cnt
               FROM complaints
           WHERE category IS NOT NULL AND category != ''
           GROUP BY category
           ORDER BY cnt DESC
           LIMIT 10"
        );
    }

    public function getComplaintsByPriority(): array {
        return $this->db->fetchAll(
            "SELECT priority, COUNT(*) AS cnt
               FROM complaints
           GROUP BY priority
           ORDER BY priority"
        );
    }

    public function getUsersByRole(): array {
        return $this->db->fetchAll(
            "SELECT r.name AS role, COUNT(u.id) AS cnt
               FROM users u
               JOIN roles r ON r.id = u.role_id
              WHERE u.is_active = 1
           GROUP BY r.name
           ORDER BY cnt DESC"
        );
    }

    public function getUsersByDepartment(): array {
        return $this->db->fetchAll(
            "SELECT d.name AS department, d.code AS code, COUNT(u.id) AS cnt
               FROM users u
               JOIN departments d ON d.id = u.department_id
              WHERE u.is_active = 1
           GROUP BY d.id, d.name, d.code
           ORDER BY cnt DESC
              LIMIT 10"
        );
    }

    public function getGaByType(): array {
        return $this->db->fetchAll(
            "SELECT session_type, COUNT(*) AS cnt
               FROM ga_sessions
           GROUP BY session_type"
        );
    }

    // ── Monthly Trends (last 12 months) ──────────────────────

    private function monthlyTrends(string $table, string $dateCol, string $periodStart = '12'): array {
        return $this->db->fetchAll(
            "SELECT DATE_FORMAT({$dateCol}, '%b %Y') AS label,
                    COUNT(*) AS cnt
               FROM {$table}
              WHERE {$dateCol} >= DATE_SUB(CURDATE(), INTERVAL {$periodStart} MONTH)
           GROUP BY DATE_FORMAT({$dateCol}, '%b %Y')
           ORDER BY MIN({$dateCol}) ASC"
        );
    }

    public function getComplaintTrends(): array {
        return $this->monthlyTrends('complaints', 'created_at');
    }

    public function getDocumentTrends(): array {
        return $this->monthlyTrends('document_requests', 'requested_at');
    }

    public function getUserTrends(): array {
        return $this->monthlyTrends('users', 'created_at');
    }

    // ── Top Items ─────────────────────────────────────────────

    public function getTopComplainers(): array {
        return $this->db->fetchAll(
            "SELECT COUNT(*) AS cnt
               FROM complaints"
        );
    }

    public function getElectionsStats(): array {
        return [
            'total'     => (int)$this->db->fetch("SELECT COUNT(*) AS cnt FROM elections")['cnt'],
            'upcoming'  => (int)$this->db->fetch("SELECT COUNT(*) AS cnt FROM elections WHERE status = 'UPCOMING'")['cnt'],
            'ongoing'   => (int)$this->db->fetch("SELECT COUNT(*) AS cnt FROM elections WHERE status = 'ONGOING'")['cnt'],
            'completed' => (int)$this->db->fetch("SELECT COUNT(*) AS cnt FROM elections WHERE status = 'COMPLETED'")['cnt'],
            'cancelled' => (int)$this->db->fetch("SELECT COUNT(*) AS cnt FROM elections WHERE status = 'CANCELLED'")['cnt'],
        ];
    }

    // ── Convenience: all data in one call ────────────────────

    public function getAll(): array {
        return [
            'users'          => $this->getUserTotals(),
            'users_by_role'  => $this->getUsersByRole(),
            'users_by_dept'  => $this->getUsersByDepartment(),
            'users_trend'    => $this->getUserTrends(),
            'complaints'     => $this->getComplaintStats(),
            'complaints_by_cat'   => $this->getComplaintsByCategory(),
            'complaints_by_prio'  => $this->getComplaintsByPriority(),
            'complaints_trend'    => $this->getComplaintTrends(),
            'documents'      => $this->getDocumentStats(),
            'documents_trend'=> $this->getDocumentTrends(),
            'clubs'          => $this->getClubStats(),
            'ga'             => $this->getGaStats(),
            'ga_by_type'     => $this->getGaByType(),
            'voting'         => $this->getVotingStats(),
            'resolutions'    => $this->getResolutionStats(),
            'news'           => $this->getNewsStats(),
            'audit'          => $this->getAuditStats(),
            'committees'     => $this->getCommitteeStats(),
            'elections'      => $this->getElectionsStats(),
        ];
    }
}
