<?php
// /cryptotrade/app/Core/Database.php
namespace App\Core;

/**
 * Développeur assignés(s) : Pierre
 * Entité : Classe 'Database' de la couche Core
 */

use PDO;
use PDOException;

class Database {
    private static $instance = null; // Ma seule instance
    private $conn; // Ma connexion PDO

    // Infos de connexion à la BDD (via config)
    private $host = DB_HOST;
    private $db_name = DB_NAME;
    private $username = DB_USER;
    private $password = DB_PASS;
    private $port = DB_PORT;

    // Constructeur privé pour le Singleton
    private function __construct() {
        $dsn = 'mysql:host=' . $this->host . ';port=' . $this->port . ';dbname=' . $this->db_name;
        // Quelques options pour PDO
        $options = [
            PDO::ATTR_PERSISTENT => true, // Connexion persistante
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, // Je veux des exceptions en cas d'erreur
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC, // Je récupère les résultats en tableau associatif
            PDO::ATTR_EMULATE_PREPARES => false, // J'utilise les vraies requêtes préparées
        ];

        try {
            // Je crée la connexion PDO
            $this->conn = new PDO($dsn, $this->username, $this->password, $options);
        } catch (PDOException $e) {
            // En vrai, je log l'erreur au lieu de faire un die()
            die("Erreur de connexion BDD: " . $e->getMessage());
        }
    }

    // Pour récupérer mon unique instance
    public static function getInstance() {
        if (self::$instance == null) {
            self::$instance = new Database();
        }
        return self::$instance;
    }

    // Pour obtenir la connexion PDO
    public function getConnection() {
        return $this->conn;
    }

    // Méthode simple pour requête ( s'assurer d'utiliser les requêtes préparées !)
    public function query($sql) {
         try {
             $stmt = $this->conn->query($sql);
             return $stmt;
         } catch (PDOException $e) {
             // Je log l'erreur
             error_log("Erreur requête BDD: " . $e->getMessage() . " SQL: " . $sql);
             return false;
         }
    }

     // J'empêche le clonage et la désérialisation
     private function __clone() { }
     public function __wakeup() { }
}
?>