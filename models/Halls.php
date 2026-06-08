<?php
class Halls {
    private $db;

    public function __construct($database) {
        $this->db = $database;
    }

    public function getAll() {
        $sql = "SELECT h.id, h.name, h.president_name, h.president_image, h.created_at,
                       COUNT(hm.id) as member_count
                FROM halls h
                LEFT JOIN hall_members hm ON h.id = hm.hall_id
                GROUP BY h.id
                ORDER BY h.name ASC";
        return $this->db->fetchAll($sql);
    }

    public function getById($id) {
        return $this->db->fetch("SELECT * FROM halls WHERE id = ?", [$id]);
    }

    public function create($data) {
        $hallId = $this->db->insert("halls", [
            "name" => $data["name"],
            "president_name" => $data["president_name"],
            "president_image" => $data["president_image"] ?? null
        ]);
        logActivity("create_hall", $_SESSION["user_id"] ?? null, ["hall_id" => $hallId, "name" => $data["name"]]);
        return $hallId;
    }

    public function update($id, $data) {
        $result = $this->db->update("halls", $data, ["id" => $id]);
        if ($result) {
            logActivity("update_hall", $_SESSION["user_id"] ?? null, ["hall_id" => $id, "name" => $data["name"] ?? ""]);
        }
        return $result;
    }

    public function delete($id) {
        $hall = $this->getById($id);
        $memberCount = $this->db->fetch("SELECT COUNT(*) as count FROM hall_members WHERE hall_id = ?", [$id])["count"];

        if ($memberCount > 0) {
            return false;
        }

        $result = $this->db->execute("DELETE FROM halls WHERE id = ?", [$id]);
        if ($result && $hall) {
            logActivity("delete_hall", $_SESSION["user_id"] ?? null, ["hall_id" => $id, "name" => $hall["name"]]);
        }
        return $result;
    }

    public function getByName($name) {
        return $this->db->fetch("SELECT * FROM halls WHERE name = ?", [$name]);
    }

    public function getMemberCount($hallId) {
        return (int)$this->db->fetch("SELECT COUNT(*) as count FROM hall_members WHERE hall_id = ?", [$hallId])["count"];
    }

    // --- Hall Members ---

    public function getMembers($hallId) {
        $sql = "SELECT hm.*, u.first_name, u.last_name, u.email, u.student_id, u.is_active
                FROM hall_members hm
                JOIN users u ON hm.user_id = u.id
                WHERE hm.hall_id = ?
                ORDER BY hm.registered_at DESC";
        return $this->db->fetchAll($sql, [$hallId]);
    }

    public function searchMember($query) {
        $sql = "SELECT hm.*, u.first_name, u.last_name, u.email, u.student_id, h.name as hall_name, h.president_name, h.president_image
                FROM hall_members hm
                JOIN users u ON hm.user_id = u.id
                JOIN halls h ON hm.hall_id = h.id
                WHERE u.student_id LIKE ? OR CONCAT(u.first_name, ' ', u.last_name) LIKE ?
                LIMIT 1";
        return $this->db->fetch($sql, ["%{$query}%", "%{$query}%"]);
    }

    public function addMember($hallId, $userId) {
        $existing = $this->db->fetch("SELECT * FROM hall_members WHERE hall_id = ? AND user_id = ?", [$hallId, $userId]);
        if ($existing) {
            return $existing["id"];
        }
        return $this->db->insert("hall_members", [
            "hall_id" => $hallId,
            "user_id" => $userId,
            "registered_at" => date("Y-m-d H:i:s")
        ]);
    }

    public function removeMember($hallId, $userId) {
        $result = $this->db->execute("DELETE FROM hall_members WHERE hall_id = ? AND user_id = ?", [$hallId, $userId]);
        if ($result) {
            logActivity("remove_hall_member", $_SESSION["user_id"] ?? null, ["hall_id" => $hallId, "user_id" => $userId]);
        }
        return $result;
    }

    // Bulk import members from Excel data
    public function importMembers($membersData) {
        $imported = 0;
        $errors = [];
        $skipped = [];

        foreach ($membersData as $row) {
            $indexNumber = trim($row["Index Number"] ?? $row["index_number"] ?? '');
            $fullName = trim($row["Full Name"] ?? $row["full_name"] ?? '');
            $hallName = trim($row["Hall Name"] ?? $row["hall_name"] ?? '');

            if (!$indexNumber || !$fullName || !$hallName) {
                $skipped[] = "Row skipped: Missing required fields";
                continue;
            }

            $hall = $this->getByName($hallName);
            if (!$hall) {
                $errors[] = "Row for '{$fullName}': Hall '{$hallName}' not found. Create the hall first.";
                continue;
            }

            $nameParts = explode(" ", $fullName, 2);
            $firstName = $nameParts[0] ?? '';
            $lastName = $nameParts[1] ?? '';

            $existingUser = $this->db->fetch("SELECT id FROM users WHERE student_id = ?", [$indexNumber]);
            if ($existingUser) {
                $this->addMember($hall["id"], $existingUser["id"]);
                $imported++;
                continue;
            }

            $studentRole = $this->db->fetch("SELECT id FROM roles WHERE name = 'STUDENT'", []);
            if (!$studentRole) {
                $errors[] = "Row for '{$fullName}': Student role not configured";
                continue;
            }

            $userId = $this->db->insert("users", [
                "first_name" => $firstName,
                "last_name" => $lastName,
                "email" => $indexNumber . "@student.srcltu.edu.gh",
                "student_id" => $indexNumber,
                "role_id" => $studentRole["id"],
                "is_active" => 1,
                "password_hash" => hashPassword(DEFAULT_TEMP_PASSWORD),
                "created_at" => date("Y-m-d H:i:s")
            ]);

            if ($userId) {
                $this->addMember($hall["id"], $userId);
                $imported++;
                logActivity("import_hall_member", $_SESSION["user_id"] ?? null, [
                    "hall_id" => $hall["id"],
                    "user_id" => $userId,
                    "index_number" => $indexNumber
                ]);
            }
        }

        return ["imported" => $imported, "errors" => $errors, "skipped" => $skipped];
    }
}