<?php
// /cryptotrade/app/Models/User.php
namespace App\Models;

/**
 * Développeur assignés(s) : Seydina
 * Entité : Classe 'User' de la couche Models
 */

use PDO;

// Constante pour le coût du hachage de mot de passe, si elle n'est pas déjà définie
if (!defined('PASSWORD_COST')) {
    define('PASSWORD_COST', 12); // J'ai mis 12, c'est un bon compromis sécurité/perf
}

class User {
    private $db;

    public function __construct(PDO $db) {
        $this->db = $db;
    }

    public function findById($id) {
        $stmt = $this->db->prepare("SELECT id, fullname, email, balance_cad, is_admin, status, last_login, created_at FROM users WHERE id = :id");
        $stmt->bindParam(":id", $id, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetch();
    }

    public function findByEmail($email) {
        $stmt = $this->db->prepare("SELECT * FROM users WHERE email = :email");
        $stmt->bindParam(":email", $email);
        $stmt->execute();
        return $stmt->fetch();
    }

    public function create($data) {
        $sql = "INSERT INTO users (fullname, email, password_hash, balance_cad, is_admin, status)
                VALUES (:fullname, :email, :password_hash, :balance_cad, :is_admin, :status)";
        $stmt = $this->db->prepare($sql);

        $hashedPassword = password_hash($data["password"], PASSWORD_BCRYPT, ["cost" => PASSWORD_COST]);
        $isAdmin = isset($data["is_admin"]) ? (bool)$data["is_admin"] : false;
        $balance = isset($data["balance_cad"]) ? $data["balance_cad"] : 10000.00; // Solde par défaut si non fourni
        $status = isset($data["status"]) ? $data["status"] : "active";

        $stmt->bindParam(":fullname", $data["fullname"]);
        $stmt->bindParam(":email", $data["email"]);
        $stmt->bindParam(":password_hash", $hashedPassword);
        $stmt->bindParam(":balance_cad", $balance);
        $stmt->bindParam(":is_admin", $isAdmin, PDO::PARAM_BOOL);
        $stmt->bindParam(":status", $status);

        return $stmt->execute();
    }

    public function updateLastLogin($id) {
        $stmt = $this->db->prepare("UPDATE users SET last_login = CURRENT_TIMESTAMP WHERE id = :id");
        $stmt->bindParam(":id", $id, PDO::PARAM_INT);
        return $stmt->execute();
    }

    public function updateBalance($id, $amountChange) {
         // Idéalement, j'utiliserais une transaction ici pour être sûr.
         // Mais là, je fais simple, en supposant que c'est assez atomique.
         $sql = "UPDATE users SET balance_cad = balance_cad + :amountChange WHERE id = :id";
         $stmt = $this->db->prepare($sql);
         $stmt->bindParam(":amountChange", $amountChange); // Peut être négatif pour déduire
         $stmt->bindParam(":id", $id, PDO::PARAM_INT);
         return $stmt->execute();
    }

    // Je mets à jour le profil (nom, email)
    public function updateProfile($id, $fullname, $email) {
        // Je vérifie si le nouvel email est déjà pris par un AUTRE utilisateur
        $stmt = $this->db->prepare("SELECT id FROM users WHERE email = :email AND id != :id");
        $stmt->bindParam(":email", $email);
        $stmt->bindParam(":id", $id, PDO::PARAM_INT);
        $stmt->execute();
        if ($stmt->fetch()) {
            // Email déjà utilisé par un autre
            return false; // J'indique que ça a échoué à cause de l'email
        }

        // C'est bon, je mets à jour
        $sql = "UPDATE users SET fullname = :fullname, email = :email WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(":fullname", $fullname);
        $stmt->bindParam(":email", $email);
        $stmt->bindParam(":id", $id, PDO::PARAM_INT);
        return $stmt->execute();
    }

    // Je mets à jour le mot de passe
    public function updatePassword($id, $currentPassword, $newPassword) {
        // 1. Je récupère le hash actuel
        $stmt = $this->db->prepare("SELECT password_hash FROM users WHERE id = :id");
        $stmt->bindParam(":id", $id, PDO::PARAM_INT);
        $stmt->execute();
        $user = $stmt->fetch();

        if (!$user) {
            return "user_not_found";
        }

        // 2. Je vérifie le mot de passe actuel
        if (!password_verify($currentPassword, $user["password_hash"])) {
            return "invalid_current_password";
        }

        // 3. Je hashe le nouveau mot de passe
        $newPasswordHash = password_hash($newPassword, PASSWORD_BCRYPT, ["cost" => PASSWORD_COST]);

        // 4. Je mets à jour le hash dans la BDD
        $sql = "UPDATE users SET password_hash = :password_hash WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(":password_hash", $newPasswordHash);
        $stmt->bindParam(":id", $id, PDO::PARAM_INT);

        if ($stmt->execute()) {
            return "success";
        } else {
            return "update_failed";
        }
    }

    public function verifyPassword($password, $hash) {
        return password_verify($password, $hash);
    }
}
?>