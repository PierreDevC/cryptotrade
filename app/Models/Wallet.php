<?php
// /cryptotrade/app/Models/Wallet.php
namespace App\Models;

use PDO;

class Wallet {
    private $db;

    public function __construct(PDO $db) {
        $this->db = $db;
    }

    // Get all holdings for a user, joining with currency info
    public function findByUserWithDetails($userId) {
        $sql = "SELECT w.quantity, c.id as currency_id, c.name, c.symbol, c.current_price_usd
                FROM wallets w
                JOIN currencies c ON w.currency_id = c.id
                WHERE w.user_id = :user_id AND w.quantity > 0"; // Only show non-zero balances
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

     // Find a specific holding entry
    public function findByUserAndCurrency($userId, $currencyId) {
        $stmt = $this->db->prepare("SELECT * FROM wallets WHERE user_id = :user_id AND currency_id = :currency_id");
        $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
        $stmt->bindParam(':currency_id', $currencyId, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetch();
    }

    // Update quantity (can be positive or negative)
    // Ensure the caller checks if the resulting quantity is valid (e.g., not negative)
    public function updateQuantity($userId, $currencyId, $quantityChange) {
         $existing = $this->findByUserAndCurrency($userId, $currencyId);

         if ($existing) {
             // Update existing entry
             $newQuantity = $existing['quantity'] + $quantityChange;
             // Ensure quantity doesn't go below zero from DB side too, although logic should prevent this
             $newQuantity = max(0, $newQuantity);

             $sql = "UPDATE wallets SET quantity = :quantity WHERE id = :id";
             $stmt = $this->db->prepare($sql);
             $stmt->bindParam(':quantity', $newQuantity);
             $stmt->bindParam(':id', $existing['id'], PDO::PARAM_INT);
             return $stmt->execute();
         } elseif ($quantityChange > 0) {
             // Create new entry if adding quantity and none exists
             return $this->createEntry($userId, $currencyId, $quantityChange);
         }
         // Do nothing if trying to subtract from a non-existent entry
         return false;
    }

    // Create a new wallet entry (usually called by updateQuantity)
    public function createEntry($userId, $currencyId, $quantity) {
         // Should only create if quantity is positive
        if ($quantity <= 0) return false;

        $sql = "INSERT INTO wallets (user_id, currency_id, quantity) VALUES (:user_id, :currency_id, :quantity)";
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
        $stmt->bindParam(':currency_id', $currencyId, PDO::PARAM_INT);
        $stmt->bindParam(':quantity', $quantity);
        return $stmt->execute();
    }
}
?>