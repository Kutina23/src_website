<?php
class SiteSettings {
    private $db;

    public function __construct($database) {
        $this->db = $database;
    }

    public function getHeroSection() {
        return [
            'tag' => 'Empowering Students Since 1992',
            'title_primary' => 'Shaping',
            'title_secondary' => 'Tomorrow\'s',
            'title_tertiary' => 'Leaders',
            'subtitle' => 'SRC Management System',
            'description' => 'A unified digital platform for the Student Representative Council of Dr. Hilla Limann Technical University — managing elections, welfare, clubs, events, and student advocacy with transparency and excellence.',
            'stats' => [
                ['number' => '4,200+', 'label' => 'Students'],
                ['number' => '8', 'label' => 'Executives'],
                ['number' => '98%', 'label' => 'Resolution Rate']
            ],
            'session' => date('Y') . ' / ' . (date('Y') + 1)
        ];
    }

    public function getAboutSection() {
        return [
            'eyebrow' => 'About the SRC',
            'title' => 'Our Mission & Purpose',
            'content' => 'The Student Representative Council (SRC) of Dr. Hilla Limann Technical University is the supreme governing body of students, committed to advocating for student rights, fostering a vibrant campus culture, and building a bridge between students and university administration.',
            'content_secondary' => 'Through this management system, we bring transparency, efficiency, and digital innovation to every layer of student governance — from elections to welfare, from clubs to complaints resolution.',
            'values' => [
                ['icon' => 'bi-balance', 'title' => 'Integrity', 'description' => 'Upholding the highest ethical standards in all SRC activities.'],
                ['icon' => 'bi-megaphone', 'title' => 'Advocacy', 'description' => 'Championing student rights, welfare, and academic needs.'],
                ['icon' => 'bi-people', 'title' => 'Inclusivity', 'description' => 'Every student represented, every voice heard equally.'],
                ['icon' => 'bi-rocket', 'title' => 'Innovation', 'description' => 'Leveraging technology for a smarter student experience.']
            ],
            'quote' => $this->getSetting('president_quote', 'The SRC exists to serve — with every policy, every decision, every action focused on student wellbeing.')
        ];
    }

    public function getDeanTitle() {
        return $this->getSetting('dean_title', 'Dean of Students');
    }

    public function getDeanSubtitle() {
        return $this->getSetting('dean_subtitle', 'Student Affairs');
    }

    public function getDeanName() {
        return $this->getSetting('dean_name', 'Akosua Boatemaa Frimpong');
    }

    public function getPresidentPostfix() {
        // First try to get from site_settings
        $postfix = $this->getSetting('president_postfix');
        if (!empty($postfix)) {
            return $postfix;
        }
        
        // Fallback to calculating from council member term if available
        // This would require access to council model, so we'll use a simpler fallback
        return '2024/' . ((int)date('Y') + 1);
    }

    public function getSetting($key, $default = null) {
        $row = $this->db->fetch("SELECT `col_value` FROM site_settings WHERE `col_key` = ?", [$key]);
        return $row ? $row['col_value'] : $default;
    }

    public function upsert($table, $where, $data) {
        // Check if record exists
        $whereConditions = [];
        $whereParams = [];
        foreach ($where as $column => $value) {
            $whereConditions[] = "$column = ?";
            $whereParams[] = $value;
        }
        $whereClause = implode(' AND ', $whereConditions);
        
        $checkSql = "SELECT COUNT(*) FROM $table WHERE $whereClause";
        $count = $this->db->fetch($checkSql, $whereParams);
        
        if ($count && $count['COUNT(*)'] > 0) {
            // Update existing record
            $setClauses = [];
            $setParams = [];
            foreach ($data as $column => $value) {
                $setClauses[] = "$column = ?";
                $setParams[] = $value;
            }
            $setClause = implode(', ', $setClauses);
            
            $updateSql = "UPDATE $table SET $setClause WHERE $whereClause";
            $allParams = array_merge($setParams, $whereParams);
            $this->db->query($updateSql, $allParams);
        } else {
            // Insert new record
            $columns = array_merge(array_keys($where), array_keys($data));
            $values = array_merge(array_values($where), array_values($data));
            $placeholders = implode(', ', array_fill(0, count($values), '?'));
            
            $insertSql = "INSERT INTO $table (" . implode(', ', $columns) . ") VALUES ($placeholders)";
            $this->db->query($insertSql, $values);
        }
        
        return $this->db->getConnection()->lastInsertId();
    }

    public function getContactInfo() {
        return [
            'location' => 'SRC Secretariat, Main Campus<br>Dr. Hilla Limann Technical University, Wa',
            'email' => 'src@hltu.edu.gh',
            'phone' => '+233 (0) 393-XXX-XXX',
            'hours' => 'Monday – Friday: 8:00 AM – 5:00 PM<br>Saturday: 9:00 AM – 1:00 PM',
            'welfare_hotline' => '050-XXX-XXXX'
        ];
    }
}
?>