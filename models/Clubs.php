<?php
class Clubs {
    private $db;

    public function __construct($database) {
        $this->db = $database;
    }

    public function getAll($filters = []) {
        $sql = "SELECT c.id, c.name, c.description, c.president_id, c.advisor_id, c.category,
                       c.status, c.founded_date, c.meeting_day, c.meeting_time, c.meeting_location,
                       c.logo_path,
                       (SELECT COUNT(*) FROM club_members WHERE club_id = c.id) as member_count,
                       p.first_name as president_first, p.last_name as president_last,
                       a.first_name as advisor_first, a.last_name as advisor_last
                FROM clubs c
                LEFT JOIN users p ON c.president_id = p.id
                LEFT JOIN users a ON c.advisor_id = a.id
                WHERE 1=1";

        $params = [];

        if (!empty($filters['status'])) {
            $sql .= " AND c.status = ?";
            $params[] = $filters['status'];
        }

        if (!empty($filters['category'])) {
            $sql .= " AND c.category = ?";
            $params[] = $filters['category'];
        }

        if (!empty($filters['search'])) {
            $sql .= " AND (c.name LIKE ? OR c.description LIKE ?)";
            $params[] = "%{$filters['search']}%";
            $params[] = "%{$filters['search']}%";
        }

        $sql .= " GROUP BY c.id ORDER BY c.name ASC";
        return $this->db->fetchAll($sql, $params);
    }

    public function getById($id) {
        $sql = "SELECT c.*,
                       p.first_name as president_first, p.last_name as president_last, p.email as president_email,
                       a.first_name as advisor_first, a.last_name as advisor_last, a.email as advisor_email,
                       (SELECT COUNT(*) FROM club_members WHERE club_id = c.id) as member_count
                 FROM clubs c
                 LEFT JOIN users p ON c.president_id = p.id
                 LEFT JOIN users a ON c.advisor_id = a.id
                 WHERE c.id = ?";
        return $this->db->fetch($sql, [$id]);
    }

    public function create($data) {
        $clubId = $this->db->insert("clubs", [
            "name" => $data["name"],
            "description" => $data["description"] ?? null,
            "president_id" => $data["president_id"] ?? null,
            "advisor_id" => $data["advisor_id"] ?? null,
            "category" => $data["category"] ?? null,
            "status" => $data["status"] ?? "ACTIVE",
            "founded_date" => $data["founded_date"] ?? null,
            "meeting_day" => $data["meeting_day"] ?? null,
            "meeting_time" => $data["meeting_time"] ?? null,
            "meeting_location" => $data["meeting_location"] ?? null,
            "logo_path" => $data["logo_path"] ?? null
        ]);
        logActivity("create_club", $_SESSION["user_id"] ?? null, ["club_id" => $clubId, "name" => $data["name"]]);
        return $clubId;
    }

    public function update($id, $data) {
        $result = $this->db->update("clubs", $data, ["id" => $id]);
        logActivity("update_club", $_SESSION["user_id"] ?? null, ["club_id" => $id, "name" => $data["name"] ?? ""]);
        return $result;
    }

    public function delete($id) {
        $club = $this->getById($id);
        $result = $this->db->execute("DELETE FROM clubs WHERE id = ?", [$id]);
        if ($result && $club) {
            logActivity("delete_club", $_SESSION["user_id"] ?? null, ["club_id" => $id, "name" => $club["name"]]);
        }
        return $result;
    }

    public function getCategories() {
        $sql = "SELECT DISTINCT category FROM clubs WHERE category IS NOT NULL AND category != '' ORDER BY category";
        return array_column($this->db->fetchAll($sql), "category");
    }

    public function getAllUsers() {
        return $this->db->fetchAll("SELECT id, first_name, last_name, email FROM users WHERE is_active = TRUE ORDER BY first_name, last_name");
    }

    // --- Club Members ---

    public function getMembers($clubId) {
        $sql = "SELECT cm.*, u.first_name, u.last_name, u.email, u.student_id, u.role_id
                FROM club_members cm
                JOIN users u ON cm.user_id = u.id
                WHERE cm.club_id = ?
                ORDER BY cm.joined_date DESC";
        return $this->db->fetchAll($sql, [$clubId]);
    }

    public function addMember($clubId, $userId, $role = "MEMBER") {
        $result = $this->db->insert("club_members", [
            "club_id" => $clubId,
            "user_id" => $userId,
            "role" => $role,
            "joined_date" => date("Y-m-d")
        ]);
        logActivity("add_club_member", $_SESSION["user_id"] ?? null, ["club_id" => $clubId, "user_id" => $userId, "role" => $role]);
        return $result;
    }

    public function removeMember($clubId, $userId) {
        $result = $this->db->execute("DELETE FROM club_members WHERE club_id = ? AND user_id = ?", [$clubId, $userId]);
        if ($result) {
            logActivity("remove_club_member", $_SESSION["user_id"] ?? null, ["club_id" => $clubId, "user_id" => $userId]);
        }
        return $result;
    }

    public function updateMemberRole($clubId, $userId, $role) {
        return $this->db->execute("UPDATE club_members SET role = ? WHERE club_id = ? AND user_id = ?", [$role, $clubId, $userId]);
    }

    public function getMemberRoles() {
        return ["MEMBER", "OFFICER", "VICE_PRESIDENT"];
    }

    // --- Public listing helpers ---

    public function getAllActive($limit = null) {
        $sql = "SELECT c.id, c.name, c.description, c.category, c.founded_date, c.logo_path,
                       (SELECT COUNT(*) FROM club_members WHERE club_id = c.id) as member_count,
                       p.first_name as president_first, p.last_name as president_last
                FROM clubs c
                LEFT JOIN users p ON p.id = (
                    SELECT cp.user_id
                    FROM club_presidents cp
                    WHERE cp.club_id = c.id AND cp.status = 'ACTIVE'
                    ORDER BY cp.assigned_at DESC
                    LIMIT 1
                )
                WHERE c.status = 'ACTIVE'
                ORDER BY member_count DESC";

        if ($limit !== null) {
            $sql .= " LIMIT " . (int)$limit;
        }

        return $this->db->fetchAll($sql);
    }

    public function getPresidents($clubId) {
        $sql = "SELECT cp.*, u.first_name, u.last_name, u.email,
                       a.first_name as assigned_by_first, a.last_name as assigned_by_last
                FROM club_presidents cp
                JOIN users u ON cp.user_id = u.id
                LEFT JOIN users a ON cp.assigned_by_id = a.id
                WHERE cp.club_id = ?
                ORDER BY cp.assigned_at DESC";
        return $this->db->fetchAll($sql, [$clubId]);
    }

    public function getActivePresident($clubId) {
        $sql = "SELECT cp.*, u.first_name, u.last_name, u.email
                FROM club_presidents cp
                JOIN users u ON cp.user_id = u.id
                WHERE cp.club_id = ? AND cp.status = 'ACTIVE'
                LIMIT 1";
        return $this->db->fetch($sql, [$clubId]);
    }

    public function assignPresident($clubId, $userId) {
        $this->db->execute("UPDATE club_presidents SET status = 'INACTIVE' WHERE club_id = ? AND status = 'ACTIVE'", [$clubId]);
        $result = $this->db->insert("club_presidents", [
            "club_id" => $clubId,
            "user_id" => $userId,
            "assigned_by_id" => $_SESSION["user_id"] ?? null,
            "status" => "ACTIVE"
        ]);
        logActivity("assign_club_president", $_SESSION["user_id"] ?? null, ["club_id" => $clubId, "user_id" => $userId]);
        return $result;
    }

    public function removePresident($clubId, $userId) {
        $result = $this->db->execute("DELETE FROM club_presidents WHERE club_id = ? AND user_id = ?", [$clubId, $userId]);
        if ($result) {
            logActivity("remove_club_president", $_SESSION["user_id"] ?? null, ["club_id" => $clubId, "user_id" => $userId]);
        }
        return $result;
    }

    // --- Registrations ---

    public function registerClub($data) {
        $clubData = [
            'name'          => $data['name'],
            'category'      => $data['category'] ?? null,
            'description'   => $data['description'] ?? null,
            'status'        => 'PENDING',
            'logo_path'     => $data['logo_path'] ?? null,
            'founded_date'  => date('Y-m-d')
        ];

        $clubId = $this->db->insert('clubs', $clubData);

        if ($clubId) {
            $this->db->insert('club_registrations', [
                'club_id'             => $clubId,
                'president_name'      => $data['president_name'],
                'president_student_id'=> $data['president_student_id'],
                'contact_email'       => $data['contact_email'],
                'contact_phone'       => $data['contact_phone'] ?? null,
                'initial_members'     => $data['initial_members'],
                'logo_path'           => $data['logo_path'] ?? null,
                'status'              => 'PENDING',
                'submitted_at'        => date('Y-m-d H:i:s')
            ]);
        }

        return $clubId;
    }

    public function getRegistrations($status = null) {
        $sql = "SELECT cr.*, c.name as club_name
                FROM club_registrations cr
                JOIN clubs c ON cr.club_id = c.id
                WHERE 1=1";
        $params = [];
        if ($status) {
            $sql .= " AND cr.status = ?";
            $params[] = $status;
        }
        $sql .= " ORDER BY cr.submitted_at DESC";
        return $this->db->fetchAll($sql, $params);
    }

    public function getRegistrationById($id) {
        $sql = "SELECT cr.*, c.name as club_name FROM club_registrations cr JOIN clubs c ON cr.club_id = c.id WHERE cr.id = ?";
        return $this->db->fetch($sql, [$id]);
    }

    public function approveRegistration($id) {
        $result = $this->updateRegistrationStatus($id, 'APPROVED');
        $registration = $this->getRegistrationById($id);
        if ($registration) {
            $this->db->execute(
                "UPDATE clubs SET status = 'ACTIVE' WHERE id = ?",
                [$registration['club_id']]
            );

            $club = $this->getById($registration['club_id']);
            $memberUserId = (int)($club['president_id'] ?? 0);

            if (!$memberUserId) {
                $user = $this->db->fetch(
                    "SELECT id FROM users WHERE email = ? AND is_active = TRUE LIMIT 1",
                    [$registration['contact_email']]
                );
                $memberUserId = (int)($user['id'] ?? 0);
            }

            if ($memberUserId > 0) {
                $this->db->upsert("club_members", [
                    "club_id" => (int)$registration['club_id'],
                    "user_id" => $memberUserId
                ], [
                    "role" => "MEMBER",
                    "joined_date" => date("Y-m-d")
                ]);
                logActivity("add_club_member", $_SESSION["user_id"] ?? null, [
                    "club_id" => (int)$registration['club_id'],
                    "user_id" => $memberUserId,
                    "role" => "MEMBER"
                ]);
            }
        }
        return $result;
    }

    public function rejectRegistration($id, $reason) {
        $result = $this->updateRegistrationStatus($id, 'REJECTED', $reason);
        $registration = $this->getRegistrationById($id);
        if ($result && $registration) {
            $this->db->execute(
                "UPDATE clubs SET status = 'INACTIVE' WHERE id = ?",
                [$registration['club_id']]
            );
        }
        return $result;
    }

    public function updateRegistrationStatus($id, $status, $rejectionReason = null) {
        return $this->db->update("club_registrations", [
            "status" => $status,
            "reviewed_at" => date("Y-m-d H:i:s"),
            "reviewed_by" => $_SESSION["user_id"] ?? null,
            "rejection_reason" => $rejectionReason
        ], ["id" => $id]);
    }

    public function countActive() {
        return $this->db->fetch("SELECT COUNT(*) as count FROM clubs WHERE status = 'ACTIVE'")["count"];
    }

    public function countInactive() {
        return $this->db->fetch("SELECT COUNT(*) as count FROM clubs WHERE status = 'INACTIVE'")["count"];
    }

    public function countSuspended() {
        return $this->db->fetch("SELECT COUNT(*) as count FROM clubs WHERE status = 'SUSPENDED'")["count"];
    }

    public function countPendingRegistrations() {
        return $this->db->fetch("SELECT COUNT(*) as count FROM club_registrations WHERE status = 'PENDING'")["count"];
    }
}
