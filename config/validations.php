<?php

class Validator {
    private $errors = [];
    private $data = [];

    public function __construct($data = []) {
        $this->data = $data;
    }

    public function validate($rules) {
        foreach ($rules as $field => $ruleString) {
            $rules = explode('|', $ruleString);
            $value = $this->data[$field] ?? null;
            
            foreach ($rules as $rule) {
                $this->applyRule($field, $rule, $value);
            }
        }
        return empty($this->errors);
    }

    private function applyRule($field, $rule, $value) {
        $ruleParts = explode(':', $rule);
        $ruleName = $ruleParts[0];
        $ruleParam = $ruleParts[1] ?? null;

        $method = 'validate' . ucfirst($ruleName);
        if (method_exists($this, $method)) {
            if (!$this->$method($field, $value, $ruleParam)) {
                $this->errors[$field][] = $this->message($ruleName, $field, $ruleParam);
            }
        }
    }

    protected function validateRequired($field, $value) {
        return !empty($value) || $value === '0';
    }

    protected function validateEmail($field, $value) {
        return empty($value) || filter_var($value, FILTER_VALIDATE_EMAIL) !== false;
    }

    protected function validateMin($field, $value, $param) {
        return strlen($value) >= (int)$param;
    }

    protected function validateMax($field, $value, $param) {
        return strlen($value) <= (int)$param;
    }

    protected function validateNumeric($field, $value) {
        return empty($value) || is_numeric($value);
    }

    protected function validateInteger($field, $value) {
        return empty($value) || filter_var($value, FILTER_VALIDATE_INT) !== false;
    }

    protected function validateString($field, $value) {
        return is_string($value) || is_numeric($value);
    }

    protected function validateBoolean($field, $value) {
        return $value === true || $value === false || $value === '0' || $value === '1' || $value === 0 || $value === 1;
    }

    protected function validateDate($field, $value) {
        return empty($value) || strtotime($value) !== false;
    }

    protected function validateUrl($field, $value) {
        return empty($value) || filter_var($value, FILTER_VALIDATE_URL) !== false;
    }

    protected function validateIn($field, $value, $param) {
        $allowed = explode(',', $param);
        return in_array($value, $allowed);
    }

    protected function validateNotIn($field, $value, $param) {
        $disallowed = explode(',', $param);
        return !in_array($value, $disallowed);
    }

    protected function validateUnique($field, $value, $param) {
        if (empty($value)) return true;
        list($table, $column) = explode(',', $param . ',' . $field);
        $stmt = db()->prepare("SELECT COUNT(*) FROM {$table} WHERE {$column} = ?");
        $stmt->execute([$value]);
        return $stmt->fetchColumn() == 0;
    }

    protected function validateExists($field, $value, $param) {
        if (empty($value)) return true;
        list($table, $column) = explode(',', $param . ',' . $field);
        $stmt = db()->prepare("SELECT COUNT(*) FROM {$table} WHERE {$column} = ?");
        $stmt->execute([$value]);
        return $stmt->fetchColumn() > 0;
    }

    protected function validateConfirmed($field, $value, $param) {
        return $value === ($this->data[$param] ?? null);
    }

    protected function validateRegex($field, $value, $param) {
        return preg_match("/^{$param}$/", $value) === 1;
    }

    protected function validateAlpha($field, $value) {
        return preg_match('/^[a-zA-Z]+$/', $value) === 1;
    }

    protected function validateAlphaDash($field, $value) {
        return preg_match('/^[a-zA-Z0-9_-]+$/', $value) === 1;
    }

    protected function validateAlphaNum($field, $value) {
        return preg_match('/^[a-zA-Z0-9]+$/', $value) === 1;
    }

    protected function validatePhone($field, $value) {
        return preg_match('/^[\+]?[0-9\-\s\(\)]{10,20}$/', $value) === 1;
    }

    protected function validateStudentId($field, $value) {
        return preg_match('/^[A-Z]{2,4}\/[0-9]{4}\/[0-9]{3}$/', $value) === 1;
    }

    protected function validatePassword($field, $value) {
        if (strlen($value) < 8) return false;
        if (!preg_match('/[A-Z]/', $value)) return false;
        if (!preg_match('/[a-z]/', $value)) return false;
        if (!preg_match('/[0-9]/', $value)) return false;
        return true;
    }

    public function errors($field = null) {
        if ($field) {
            return $this->errors[$field] ?? [];
        }
        return $this->errors;
    }

    public function firstError($field) {
        return $this->errors[$field][0] ?? null;
    }

    public function hasError($field) {
        return isset($this->errors[$field]);
    }

    private function message($rule, $field, $param = null) {
        $messages = [
            'required' => "The {$field} field is required.",
            'email' => "The {$field} must be a valid email address.",
            'min' => "The {$field} must be at least {$param} characters.",
            'max' => "The {$field} may not be greater than {$param} characters.",
            'numeric' => "The {$field} must be a number.",
            'integer' => "The {$field} must be an integer.",
            'string' => "The {$field} must be a string.",
            'boolean' => "The {$field} must be true or false.",
            'date' => "The {$field} is not a valid date.",
            'url' => "The {$field} must be a valid URL.",
            'in' => "The selected {$field} is invalid.",
            'not_in' => "The selected {$field} is invalid.",
            'unique' => "The {$field} has already been taken.",
            'exists' => "The selected {$field} is invalid.",
            'confirmed' => "The {$field} confirmation does not match.",
            'regex' => "The {$field} format is invalid.",
            'alpha' => "The {$field} may only contain letters.",
            'alpha_dash' => "The {$field} may only contain letters, numbers, dashes, and underscores.",
            'alpha_num' => "The {$field} may only contain letters and numbers.",
            'phone' => "The {$field} must be a valid phone number.",
            'student_id' => "The {$field} must be in format: CS/2023/001",
            'password' => "The {$field} must be at least 8 characters with uppercase, lowercase, and number."
        ];
        return $messages[$rule] ?? "The {$field} field is invalid.";
    }
}

function validate($data, $rules) {
    $validator = new Validator($data);
    return $validator->validate($rules) ? true : $validator;
}

function validateRequest($rules) {
    $data = $_POST;
    $files = $_FILES;
    foreach ($files as $key => $file) {
        if ($file['error'] !== UPLOAD_ERR_NO_FILE) {
            $data[$key] = $file;
        }
    }
    return validate($data, $rules);
}

function validateUser($data, $isUpdate = false) {
    $rules = [
        'email' => 'required|email|unique:users,email',
        'first_name' => 'required|min:2|max:50',
        'last_name' => 'required|min:2|max:50',
        'student_id' => 'required|student_id|unique:users,student_id',
        'phone' => 'required|phone',
        'role' => 'required|in:PRO,PRESIDENT,DIRECTOR ICT,DEAN,STUDENT'
    ];
    
    if (!$isUpdate) {
        $rules['password'] = 'required|password';
    }
    
    if ($isUpdate) {
        $rules['email'] = 'required|email';
        $rules['student_id'] = 'required|student_id';
    }
    
    return validate($data, $rules);
}

function validateComplaint($data) {
    return validate($data, [
        'title' => 'required|min:5|max:100',
        'description' => 'required|min:10',
        'category' => 'required|in:Academic,Financial,Welfare,Housing,Health,Other',
        'priority' => 'required|in:Low,Medium,High,Urgent'
    ]);
}

function validateDocumentRequest($data) {
    return validate($data, [
        'document_type' => 'required|in:Transcript,Certificate,Recommendation,Other',
        'reason' => 'required|min:10',
        'delivery_method' => 'required|in:Pickup,Email'
    ]);
}

function validateElection($data) {
    return validate($data, [
        'title' => 'required|min:5|max:100',
        'position' => 'required|min:2|max:50',
        'start_date' => 'required|date',
        'end_date' => 'required|date',
        'eligible_roles' => 'required'
    ]);
}

function validateClub($data) {
    return validate($data, [
        'name' => 'required|min:3|max:100',
        'description' => 'required|min:10',
        'category' => 'required|in:Academic,Cultural,Social,Religious,Sports,Other',
        'president_id' => 'required|exists:users,id'
    ]);
}

function validateEventScheduling(array $data): array {
    $errors = [];
    $location = trim($data['event_location'] ?? '');
    $date = $data['event_date'] ?? '';
    $startTime = $data['event_start_time'] ?? '';
    $excludeId = $data['exclude_event_id'] ?? null;

    if (!$location || !$date || !$startTime) {
        return $errors;
    }

    $eventStart = new DateTime("$date $startTime");
    $bufferHours = 3;
    $minBuffer = new DateInterval("PT{$bufferHours}H");

    $db = Database::getInstance();

    // Check conflicts in news (EVENT category)
    $newsSql = "SELECT id, title, published_at, excerpt FROM news 
                WHERE category = 'EVENT' AND status = 'PUBLISHED' 
                AND DATE(published_at) = ?";
    $existingEvents = $db->fetchAll($newsSql, [$date]);

    foreach ($existingEvents as $existing) {
        if ($excludeId && (int)$existing['id'] === (int)$excludeId) continue;

        $excerpt = $existing['excerpt'] ?? '';
        
        // Extract time and location from excerpt
        // Format: "🕐 6:13 AM · 📍 Unity Hall\nDescription" or "6:13 AM · Unity Hall"
        $existingLocation = '';
        $existingTime = '';
        
        // Match time in format "6:13 AM" (12-hour with AM/PM)
        if (preg_match('/(\d{1,2}:\d{2}\s*(?:[AP]M))/i', $excerpt, $timeMatch)) {
            $existingTime = trim($timeMatch[1]);
        }
        
        // Match location - extract after " · " separator and 📍 emoji
        // Split by separator and take second part
        if (strpos($excerpt, ' · ') !== false) {
            $parts = explode(' · ', $excerpt);
            if (count($parts) >= 2) {
                $locPart = $parts[1];
                // Remove 📍 emoji and clean up - stop at newline
                $existingLocation = trim(str_replace('📍', '', $locPart));
                $existingLocation = preg_replace('/\s*[:：].*$/u', '', $existingLocation); // Remove trailing description
                // Get just the location part (before newline)
                $existingLocation = preg_replace('/\s*[\n\r].*$/u', '', $existingLocation);
            }
        }
        
        if (!$existingLocation || !$existingTime) {
            continue;
        }
        
        if (strtolower($existingLocation) !== strtolower($location)) continue;

        try {
            $existingStart = new DateTime($existing['published_at']);
            $parsedTime = DateTime::createFromFormat('g:i A', $existingTime)
                ?: DateTime::createFromFormat('H:i', $existingTime)
                ?: DateTime::createFromFormat('h:i A', $existingTime);
            
            if (!$parsedTime) continue;
            
            $existingStart->setTime($parsedTime->format('H'), $parsedTime->format('i'));

            // 3-hour buffer check: conflicting if time slots overlap within buffer window
            $conflictEnd = (clone $eventStart)->add($minBuffer);
            $existingEnd = (clone $existingStart)->add($minBuffer);

            $hasConflict = $eventStart < $existingEnd && $existingStart < $conflictEnd;
            if ($hasConflict) {
                $errors[] = "Scheduling conflict: another event (\"{$existing['title']}\") is already booked at \"$location\" within 3 hours of this time.";
                break;
            }
        } catch (Exception $e) {
            continue;
        }
    }

    // Check conflicts in elections
    $elecSql = "SELECT id, title, election_date, start_time FROM elections 
                WHERE is_active = 1 
                AND DATE(election_date) = ?";
    $existingElections = $db->fetchAll($elecSql, [$date]);

    foreach ($existingElections as $existing) {
        if ($excludeId && (int)$existing['id'] === (int)$excludeId) continue;
        $existingLocation = $existing['location'] ?? '';
        if (!$existingLocation || strtolower($existingLocation) !== strtolower($location)) continue;
        if (!$existing['start_time']) continue;

        try {
            $existingStart = new DateTime($existing['election_date'] . ' ' . $existing['start_time']);
            $conflictEnd = (clone $eventStart)->add($minBuffer);
            $existingEnd = (clone $existingStart)->add($minBuffer);

            $hasConflict = $eventStart < $existingEnd && $existingStart < $conflictEnd;
            if ($hasConflict) {
                $errors[] = "Scheduling conflict: an election ({$existing['title']}) is already scheduled at \"$location\" within 3 hours.";
                break;
            }
        } catch (Exception $e) {
            continue;
        }
    }

    // Check conflicts in GA sessions
    $gaSql = "SELECT id, title, scheduled_datetime, location FROM ga_sessions 
              WHERE status IN ('SCHEDULED', 'IN_PROGRESS')
              AND DATE(scheduled_datetime) = ?";
    $existingSessions = $db->fetchAll($gaSql, [$date]);

    foreach ($existingSessions as $existing) {
        if ($excludeId && (int)$existing['id'] === (int)$excludeId) continue;
        $existingLocation = $existing['location'] ?? '';
        if (!$existingLocation || strtolower($existingLocation) !== strtolower($location)) continue;

        try {
            $existingStart = new DateTime($existing['scheduled_datetime']);
            $conflictEnd = (clone $eventStart)->add($minBuffer);
            $existingEnd = (clone $existingStart)->add($minBuffer);

            $hasConflict = $eventStart < $existingEnd && $existingStart < $conflictEnd;
            if ($hasConflict) {
                $errors[] = "Scheduling conflict: a GA session ({$existing['title']}) is already scheduled at \"$location\" within 3 hours.";
                break;
            }
        } catch (Exception $e) {
            continue;
        }
    }

    return $errors;
}

function validateGASession($data) {
    return validate($data, [
        'title' => 'required|min:5|max:100',
        'scheduled_at' => 'required|date',
        'location' => 'required|min:3|max:100',
        'agenda' => 'required|min:10'
    ]);
}
?>