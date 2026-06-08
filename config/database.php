<?php
require_once __DIR__ . '/email.php';

class Database {
    private static $instance = null;
    private $connection;
    private $host;
    private $dbname;
    private $username;
    private $password;
    private $charset = "utf8mb4";
    private static $usersTableChecked = false;

private function __construct() {
        $config = getDbConfig();
        $this->host = $config['host'];
        $this->dbname = $config['dbname'];
        $this->username = $config['username'];
        $this->password = $config['password'];
        $dsn = "mysql:host={$this->host};dbname={$this->dbname};charset={$this->charset}";
        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
            PDO::ATTR_PERSISTENT         => true,
        ];

        try {
            $this->connection = new PDO($dsn, $this->username, $this->password, $options);
        } catch (PDOException $e) {
            throw new Exception("Database connection failed: " . $e->getMessage());
        }
    }

    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function getConnection() {
        return $this->connection;
    }

    public function query($sql, $params = []) {
        $stmt = $this->connection->prepare($sql);
        $stmt->execute($params);
        return $stmt;
    }

    public function fetch($sql, $params = []) {
        return $this->query($sql, $params)->fetch();
    }

    public function fetchAll($sql, $params = []) {
        return $this->query($sql, $params)->fetchAll();
    }

    public function insert($table, $data) {
        $keys = array_keys($data);
        $placeholders = array_map(fn($key) => ":$key", $keys);
        $sql = "INSERT INTO {$table} (" . implode(", ", $keys) . ") VALUES (" . implode(", ", $placeholders) . ")";
        $stmt = $this->connection->prepare($sql);
        $stmt->execute($data);
        return $this->connection->lastInsertId();
    }

    public function update($table, $data, $where) {
        $set = array_map(fn($key) => "$key = :$key", array_keys($data));
        $whereConditions = array_map(fn($key) => "$key = :where_$key", array_keys($where));
        $wherePlaceholders = array_combine(
            array_map(fn($key) => "where_$key", array_keys($where)),
            array_values($where)
        );
        $allParams = array_merge($data, $wherePlaceholders);
        $sql = "UPDATE {$table} SET " . implode(", ", $set) . " WHERE " . implode(" AND ", $whereConditions);
        $stmt = $this->connection->prepare($sql);
        return $stmt->execute($allParams);
    }

    public function upsert($table, $where, $data) {
        $whereConditions = [];
        $whereParams = [];
        foreach ($where as $column => $value) {
            $whereConditions[] = "$column = ?";
            $whereParams[] = $value;
        }
        $whereClause = implode(" AND ", $whereConditions);
        
        $checkSql = "SELECT COUNT(*) FROM $table WHERE $whereClause";
        $count = $this->query($checkSql, $whereParams)->fetchColumn();
        
        if ($count > 0) {
            $setClauses = [];
            $setParams = [];
            foreach ($data as $column => $value) {
                $setClauses[] = "$column = ?";
                $setParams[] = $value;
            }
            $setClause = implode(", ", $setClauses);
            
            $updateSql = "UPDATE $table SET $setClause WHERE $whereClause";
            $allParams = array_merge($setParams, $whereParams);
            $this->query($updateSql, $allParams);
        } else {
            $columns = array_merge(array_keys($where), array_keys($data));
            $values = array_merge(array_values($where), array_values($data));
            $placeholders = implode(", ", array_fill(0, count($values), "?"));
            
            $insertSql = "INSERT INTO $table (" . implode(", ", $columns) . ") VALUES ($placeholders)";
            $this->query($insertSql, $values);
        }
    }

    public function delete($table, $where) {
        if (is_array($where)) {
            $whereConditions = array_map(fn($key) => "$key = ?", array_keys($where));
            $whereParams     = array_values($where);
            $sql = "DELETE FROM {$table} WHERE " . implode(" AND ", $whereConditions);
            $stmt = $this->connection->prepare($sql);
            $stmt->execute($whereParams);
            return $stmt->rowCount();
        }
        $sql = "DELETE FROM {$table} WHERE {$where}";
        return $this->connection->exec($sql);
    }

    public function execute($sql, $params = []) {
        $stmt = $this->connection->prepare($sql);
        return $stmt->execute($params);
    }
}

function db() {
    return Database::getInstance();
}
?>
