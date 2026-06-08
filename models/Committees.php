<?php
class Committees {
    private $db;

    public function __construct($database) {
        $this->db = $database;
    }

    public function getAll() {
        $sql = "SELECT c.*,
                       (SELECT COUNT(*) FROM committee_members cm WHERE cm.committee_id = c.id) as member_count
                FROM committees c
                ORDER BY c.name ASC";
        return $this->db->fetchAll($sql);
    }

    public function getById($id) {
        $sql = "SELECT * FROM committees WHERE id = ?";
        return $this->db->fetch($sql, [$id]);
    }

    public function getBySlug($slug) {
        $sql = "SELECT * FROM committees WHERE LOWER(slug) = LOWER(?) OR LOWER(name) = LOWER(?)";
        return $this->db->fetch($sql, [$slug, $slug]);
    }

    public function create($data) {
        return $this->db->insert('committees', $data);
    }

    public function update($id, $data) {
        return $this->db->update('committees', $data, ['id' => $id]);
    }

    public function delete($id) {
        return $this->db->delete('committees', "id = $id");
    }
}

class CommitteeMembers {
    private $db;
    private $useEnhancedSchema = true;

    public function __construct($database) {
        $this->db = $database;
        // Check if enhanced schema columns exist
        $this->checkSchema();
    }

    private function checkSchema() {
        $sql = "SHOW COLUMNS FROM committee_members LIKE 'role_type'";
        $result = $this->db->fetch($sql);
        $this->useEnhancedSchema = !empty($result);
    }

    public function getByCommittee($committeeId) {
        if (!$committeeId) return [];

        if ($this->useEnhancedSchema) {
            $sql = "SELECT id, name, department, role_type, role_order, display_order, is_active
                    FROM committee_members
                    WHERE committee_id = ? AND is_active = 1
                    ORDER BY role_order ASC, display_order ASC";
            $members = $this->db->fetchAll($sql, [$committeeId]);

            foreach ($members as &$member) {
                if (empty($member['name'])) {
                    $sql2 = "SELECT cm.committee_id, cm.user_id,
                                    u.first_name, u.last_name, u.email, d.name as user_department
                             FROM committee_members cm
                             LEFT JOIN users u ON cm.user_id = u.id
                             LEFT JOIN departments d ON u.department_id = d.id
                             WHERE cm.id = ?";
                    $fallback = $this->db->fetch($sql2, [$member['id']]);
                    if ($fallback) {
                        $member['name'] = trim(($fallback['first_name'] ?? '') . ' ' . ($fallback['last_name'] ?? ''));
                        $member['department'] = $fallback['user_department'] ?? '';
                    }
                }
                $member['role_type'] = $member['role_type'] ?? 'member';
            }
        } else {
            // Old schema - join with users table
            $sql = "SELECT cm.committee_id, cm.user_id,
                           u.first_name, u.last_name, u.email, d.name as user_department,
                           cm.role as role_type
                    FROM committee_members cm
                    LEFT JOIN users u ON cm.user_id = u.id
                    LEFT JOIN departments d ON u.department_id = d.id
                    WHERE cm.committee_id = ?";
            $members = $this->db->fetchAll($sql, [$committeeId]);

            foreach ($members as &$member) {
                $member['department'] = $member['user_department'] ?? '';
                $member['name'] = trim(($member['first_name'] ?? '') . ' ' . ($member['last_name'] ?? ''));
                $member['role_type'] = $member['role_type'] ?? 'member';
                if (empty($member['name'])) {
                    $member['name'] = 'Member #' . $member['user_id'];
                }
                unset($member['user_department']);
            }
        }
        return $members;
    }

    public function getById($committeeId, $memberId = null) {
        if (!$committeeId) return null;

        if ($this->useEnhancedSchema) {
            $sql = "SELECT id, name, department, role_type, role_order, display_order, is_active
                    FROM committee_members
                    WHERE committee_id = ?";
            $params = [$committeeId];

            if ($memberId) {
                $sql .= " AND id = ?";
                $params[] = $memberId;
            }

            $member = $this->db->fetch($sql, $params);

            if ($member) {
                if (empty($member['name'])) {
                    $sql2 = "SELECT cm.committee_id, cm.user_id,
                                    u.first_name, u.last_name, u.email, d.name as user_department
                             FROM committee_members cm
                             LEFT JOIN users u ON cm.user_id = u.id
                             LEFT JOIN departments d ON u.department_id = d.id
                             WHERE cm.id = ?";
                    $fallback = $this->db->fetch($sql2, [$member['id']]);
                    if ($fallback) {
                        $member['name'] = trim(($fallback['first_name'] ?? '') . ' ' . ($fallback['last_name'] ?? ''));
                        $member['department'] = $fallback['user_department'] ?? '';
                    }
                }
                $member['role_type'] = $member['role_type'] ?? 'member';
            }
            return $member;
        } else {
            // Old schema
            $sql = "SELECT cm.committee_id, cm.user_id,
                           u.first_name, u.last_name, u.email, d.name as user_department,
                           cm.role as role_type
                    FROM committee_members cm
                    LEFT JOIN users u ON cm.user_id = u.id
                    LEFT JOIN departments d ON u.department_id = d.id
                    WHERE cm.committee_id = ? AND cm.user_id = ?";
            $member = $this->db->fetch($sql, [$committeeId, $memberId ?: 0]);
            if ($member) {
                $member['department'] = $member['user_department'] ?? '';
                $member['name'] = trim(($member['first_name'] ?? '') . ' ' . ($member['last_name'] ?? ''));
                if (empty($member['name'])) {
                    $member['name'] = 'Member #' . $member['user_id'];
                }
                $member['role_type'] = $member['role_type'] ?? 'member';
                $member['id'] = $member['user_id'];
                unset($member['user_department']);
            }
            return $member;
        }
    }

    public function create($data) {
        return $this->db->insert('committee_members', $data);
    }

    public function update($committeeId, $userId, $data) {
        if ($userId) {
            return $this->db->update('committee_members', $data, ['id' => $userId, 'committee_id' => $committeeId]);
        }
        return false;
    }

    public function delete($memberId) {
        return $this->db->delete('committee_members', ['id' => $memberId]);
    }

    public function getLeadership($committeeId) {
        if (!$committeeId) return [];

        if ($this->useEnhancedSchema) {
            $sql = "SELECT id, name, department, role_type, role_order, display_order, is_active
                    FROM committee_members
                    WHERE committee_id = ? AND is_active = 1
                    AND (LOWER(role_type) IN ('chairperson', 'secretary', 'commissioner') OR role_type IS NULL OR role_type = '' OR role_order <= 2)
                    ORDER BY role_order ASC, display_order ASC";
            $members = $this->db->fetchAll($sql, [$committeeId]);

            foreach ($members as &$member) {
                if (empty($member['name'])) {
                    $sql2 = "SELECT cm.committee_id, cm.user_id,
                                    u.first_name, u.last_name, u.email, d.name as user_department
                             FROM committee_members cm
                             LEFT JOIN users u ON cm.user_id = u.id
                             LEFT JOIN departments d ON u.department_id = d.id
                             WHERE cm.id = ?";
                    $fallback = $this->db->fetch($sql2, [$member['id']]);
                    if ($fallback) {
                        $member['name'] = trim(($fallback['first_name'] ?? '') . ' ' . ($fallback['last_name'] ?? ''));
                        $member['department'] = $fallback['user_department'] ?? '';
                    }
                }
                if (empty($member['role_type'])) {
                    $member['role_type'] = $member['role_order'] == 1 ? 'chairperson' : ($member['role_order'] == 2 ? 'secretary' : 'member');
                }
            }
        } else {
            // Old schema - get all members and assign leadership roles
            $sql = "SELECT cm.committee_id, cm.user_id,
                           u.first_name, u.last_name, u.email, d.name as user_department
                    FROM committee_members cm
                    LEFT JOIN users u ON cm.user_id = u.id
                    LEFT JOIN departments d ON u.department_id = d.id
                    WHERE cm.committee_id = ?
                    LIMIT 3";
            $members = $this->db->fetchAll($sql, [$committeeId]);

            foreach ($members as $i => &$member) {
                $member['department'] = $member['user_department'] ?? '';
                $member['name'] = trim(($member['first_name'] ?? '') . ' ' . ($member['last_name'] ?? ''));
                if (empty($member['name'])) {
                    $member['name'] = 'Member #' . $member['user_id'];
                }
                $member['role_type'] = $i === 0 ? 'chairperson' : ($i === 1 ? 'secretary' : 'member');
                $member['id'] = $member['user_id'];
                unset($member['user_department']);
            }
        }
        return $members;
    }

    public function getMembers($committeeId, $leadershipIds = []) {
        if (!$committeeId) return [];

        $sql = "SELECT id, name, department, role_type, role_order, display_order, is_active
                FROM committee_members
                WHERE committee_id = ? AND is_active = 1
                AND (LOWER(role_type) = 'member' OR role_type IS NULL OR role_type = '' OR LOWER(role_type) = 'member')";

        $params = [$committeeId];

        if (!empty($leadershipIds)) {
            $placeholders = implode(',', array_fill(0, count($leadershipIds), '?'));
            $sql .= " AND id NOT IN ($placeholders)";
            $params = array_merge($params, $leadershipIds);
        }

        $members = $this->db->fetchAll($sql, $params);

        foreach ($members as &$member) {
            if (empty($member['name'])) {
                $sql2 = "SELECT cm.committee_id, cm.user_id,
                                u.first_name, u.last_name, u.email, d.name as user_department
                         FROM committee_members cm
                         LEFT JOIN users u ON cm.user_id = u.id
                         LEFT JOIN departments d ON u.department_id = d.id
                         WHERE cm.id = ?";
                $fallback = $this->db->fetch($sql2, [$member['id']]);
                if ($fallback) {
                    $member['name'] = trim(($fallback['first_name'] ?? '') . ' ' . ($fallback['last_name'] ?? ''));
                    $member['department'] = $fallback['user_department'] ?? '';
                }
            }
            $member['role_type'] = $member['role_type'] ?? 'member';
        }
        return $members;
    }
}

class CommitteeMandates {
    private $db;

    public function __construct($database) {
        $this->db = $database;
    }

    public function getByCommittee($committeeId) {
        $sql = "SELECT * FROM committee_mandates WHERE committee_id = ? ORDER BY mandate_order ASC";
        return $this->db->fetchAll($sql, [$committeeId]);
    }

    public function getById($id) {
        $sql = "SELECT * FROM committee_mandates WHERE id = ?";
        return $this->db->fetch($sql, [$id]);
    }

    public function create($data) {
        return $this->db->insert('committee_mandates', $data);
    }

    public function update($id, $data) {
        return $this->db->update('committee_mandates', $data, ['id' => $id]);
    }

    public function delete($id) {
        return $this->db->delete('committee_mandates', "id = $id");
    }
}
?>