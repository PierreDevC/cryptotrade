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

    // NEW: Update user profile (fullname, email)
    public function updateProfile($id, $fullname, $email) {
        // Check if the new email is already taken by ANOTHER user
        $stmt = $this->db->prepare("SELECT id FROM users WHERE email = :email AND id != :id");
        $stmt->bindParam(':email', $email);
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        if ($stmt->fetch()) {
            // Email already exists for another user
            return false; // Indicate failure due to email conflict
        }

        // Proceed with update
        $sql = "UPDATE users SET fullname = :fullname, email = :email WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':fullname', $fullname);
        $stmt->bindParam(':email', $email);
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        return $stmt->execute();
    }

    // NEW: Update user password
    public function updatePassword($id, $currentPassword, $newPassword) {
        // 1. Get the current password hash
        $stmt = $this->db->prepare("SELECT password_hash FROM users WHERE id = :id");
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        $user = $stmt->fetch();

        if (!$user) {
            return 'user_not_found';
        }

        // 2. Verify the current password
        if (!password_verify($currentPassword, $user['password_hash'])) {
            return 'invalid_current_password';
        }

        // 3. Hash the new password
        $newPasswordHash = password_hash($newPassword, PASSWORD_BCRYPT, ['cost' => PASSWORD_COST]);

        // 4. Update the password hash in the database
        $sql = "UPDATE users SET password_hash = :password_hash WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':password_hash', $newPasswordHash);
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);

        if ($stmt->execute()) {
            return 'success';
        } else {
            return 'update_failed';
        }
    }

    public function verifyPassword($password, $hash) {
        return password_verify($password, $hash);
    }
}
?>