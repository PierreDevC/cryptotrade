<?php
// /cryptotrade/app/Core/Database.php
namespace App\Core;

use PDO;
use PDOException;

class Database {
    private static $instance = null;
    private $conn;

    private $host = DB_HOST;
    private $db_name = DB_NAME;
    private $username = DB_USER;
    private $password = DB_PASS;

    private function __construct() {
        $dsn = 'mysql:host=' . $this->host . ';dbname=' . $this->db_name;
        $options = [
            PDO::ATTR_PERSISTENT => true,
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC, // Fetch as associative array
            PDO::ATTR_EMULATE_PREPARES => false,
        ];

        try {
            $this->conn = new PDO($dsn, $this->username, $this->password, $options);
        } catch (PDOException $e) {
            // In a real app, log this error instead of echoing
            die("Database Connection Error: " . $e->getMessage());
        }
    }

    public static function getInstance() {
        if (self::$instance == null) {
            self::$instance = new Database();
        }
        return self::$instance;
    }

    public function getConnection() {
        return $this->conn;
    }

    // Optional simple query helper (use prepared statements for security!)
    public function query($sql) {
         try {
             $stmt = $this->conn->query($sql);
             return $stmt;
         } catch (PDOException $e) {
             // Log error
             error_log("DB Query Error: " . $e->getMessage() . " SQL: " . $sql);
             return false;
         }
    }

     // Prevent cloning and unserialization
     private function __clone() { }
     public function __wakeup() { }
}
?>