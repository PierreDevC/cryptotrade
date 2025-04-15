<?php
// /cryptotrade/app/Models/User.php
namespace App\Models;

use PDO;

class User {
    private $db;

    public function __construct(PDO $db) {
        $this->db = $db;
    }

    public function findById($id) {
        $stmt = $this->db->prepare("SELECT id, fullname, email, balance_cad, is_admin, status, last_login, created_at FROM users WHERE id = :id");
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetch();
    }

    public function findByEmail($email) {
        $stmt = $this->db->prepare("SELECT * FROM users WHERE email = :email");
        $stmt->bindParam(':email', $email);
        $stmt->execute();
        return $stmt->fetch();
    }

    public function create($data) {
        $sql = "INSERT INTO users (fullname, email, password_hash, balance_cad, is_admin, status)
                VALUES (:fullname, :email, :password_hash, :balance_cad, :is_admin, :status)";
        $stmt = $this->db->prepare($sql);

        $hashedPassword = password_hash($data['password'], PASSWORD_BCRYPT, ['cost' => PASSWORD_COST]);
        $isAdmin = isset($data['is_admin']) ? (bool)$data['is_admin'] : false;
        $balance = isset($data['balance_cad']) ? $data['balance_cad'] : 10000.00; // Default balance
        $status = isset($data['status']) ? $data['status'] : 'active';

        $stmt->bindParam(':fullname', $data['fullname']);
        $stmt->bindParam(':email', $data['email']);
        $stmt->bindParam(':password_hash', $hashedPassword);
        $stmt->bindParam(':balance_cad', $balance);
        $stmt->bindParam(':is_admin', $isAdmin, PDO::PARAM_BOOL);
        $stmt->bindParam(':status', $status);

        return $stmt->execute();
    }

    public function updateLastLogin($id) {
        $stmt = $this->db->prepare("UPDATE users SET last_login = CURRENT_TIMESTAMP WHERE id = :id");
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        return $stmt->execute();
    }

    public function updateBalance($id, $amountChange) {
         // Use transaction for safety if multiple updates happen elsewhere
         // For simplicity here, we assume it's atomic enough
         $sql = "UPDATE users SET balance_cad = balance_cad + :amountChange WHERE id = :id";
         $stmt = $this->db->prepare($sql);
         $stmt->bindParam(':amountChange', $amountChange); // Can be negative for deduction
         $stmt->bindParam(':id', $id, PDO::PARAM_INT);
         return $stmt->execute();
    }

    public function verifyPassword($password, $hash) {
        return password_verify($password, $hash);
    }
}
?>