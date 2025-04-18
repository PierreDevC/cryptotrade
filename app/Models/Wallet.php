<?php
// /cryptotrade/app/Models/Wallet.php
namespace App\Models;

/**
 * Développeur assignés(s) : Moses
 * Entité : Classe 'Wallet' de la couche Models
 */

use PDO;

class Wallet {
    private $db;

    public function __construct(PDO $db) {
        $this->db = $db;
    }

    // Je prends tous les actifs d'un user avec les infos devise.
    public function findByUserWithDetails($userId) {
        $sql = "SELECT w.quantity, c.id as currency_id, c.name, c.symbol, c.current_price_usd
                FROM wallets w
                JOIN currencies c ON w.currency_id = c.id
                WHERE w.user_id = :user_id AND w.quantity > 0"; // Je montre que les soldes > 0.
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(":user_id", $userId, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

     // Je cherche une entrée précise.
    public function findByUserAndCurrency($userId, $currencyId) {
        $stmt = $this->db->prepare("SELECT * FROM wallets WHERE user_id = :user_id AND currency_id = :currency_id");
        $stmt->bindParam(":user_id", $userId, PDO::PARAM_INT);
        $stmt->bindParam(":currency_id", $currencyId, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetch();
    }

    // Je mets à jour la quantité (+ ou -).
    // La logique appelante doit vérifier que la quantité reste >= 0.
    public function updateQuantity($userId, $currencyId, $quantityChange) {
         $existing = $this->findByUserAndCurrency($userId, $currencyId);

         if ($existing) {
             // Je mets à jour l'entrée existante.
             $newQuantity = $existing["quantity"] + $quantityChange;
             // Je m'assure que ça reste >= 0 côté BDD aussi.
             $newQuantity = max(0, $newQuantity);

             $sql = "UPDATE wallets SET quantity = :quantity WHERE id = :id";
             $stmt = $this->db->prepare($sql);
             $stmt->bindParam(":quantity", $newQuantity);
             $stmt->bindParam(":id", $existing["id"], PDO::PARAM_INT);
             return $stmt->execute();
         } elseif ($quantityChange > 0) {
             // J'en crée une si on ajoute et qu'elle n'existe pas.
             return $this->createEntry($userId, $currencyId, $quantityChange);
         }
         // Je fais rien si on soustrait d'une entrée qui n'existe pas.
         return false;
    }

    // Je crée une entrée de portefeuille (souvent via updateQuantity).
    public function createEntry($userId, $currencyId, $quantity) {
         // Je crée seulement si quantité > 0.
        if ($quantity <= 0) return false;

        $sql = "INSERT INTO wallets (user_id, currency_id, quantity) VALUES (:user_id, :currency_id, :quantity)";
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(":user_id", $userId, PDO::PARAM_INT);
        $stmt->bindParam(":currency_id", $currencyId, PDO::PARAM_INT);
        $stmt->bindParam(":quantity", $quantity);
        return $stmt->execute();
    }
}
?>