<?php
// /cryptotrade/app/Controllers/AuthController.php
namespace App\Controllers;

/**
 * Développeur assignés(s) : Pierre
 * Entité : Classe 'AuthController' de la couche Controllers
 */

use App\Core\Session;
use App\Core\Request;
use App\Models\User;
use App\Utils\Csrf;
use PDO;

class AuthController {
    private $db;
    private $request;
    private $userModel;

    public function __construct(PDO $db) {
        $this->db = $db;
        $this->request = new Request(); // J'instancie Request ici
        $this->userModel = new User($db);
    }

    // Ces méthodes sont moins pertinentes maintenant que le HTML est servi directement
    // public function showLogin() { require 'login.html'; }
    // public function showSignup() { require 'signup.html'; }

    public function login() {
        Csrf::protect($this->request);

        $body = $this->request->getBody();
        $email = $body['email'] ?? null;
        $password = $body['password'] ?? null;

        if (empty($email) || empty($password)) {
             Session::flash('error', 'L\'email et le mot de passe sont requis.');
             header('Location: ' . BASE_URL . '/login');
             exit;
        }

        $user = $this->userModel->findByEmail($email);

        if ($user && $this->userModel->verifyPassword($password, $user['password_hash'])) {
            if ($user['status'] !== 'active') {
                Session::flash('error', 'Le compte est inactif.');
                header('Location: ' . BASE_URL . '/login');
                exit;
            }
            // Connexion réussie
            Session::set('user_id', $user['id']);
            Session::set('user_fullname', $user['fullname']);
            Session::set('is_admin', (bool)$user['is_admin']);
            $this->userModel->updateLastLogin($user['id']);

            // Redirection vers le tableau de bord
            header('Location: ' . BASE_URL . '/dashboard');
            exit;
        } else {
            // Échec de la connexion
            Session::flash('error', 'Invalid email or password.');
            header('Location: ' . BASE_URL . '/login');
            exit;
        }
    }

    public function signup() {
        Csrf::protect($this->request);

        $body = $this->request->getBody();
        $fullname = $body['fullName'] ?? null; // Correspond au nom du champ dans le formulaire HTML
        $email = $body['email'] ?? null;
        $password = $body['password'] ?? null;
        $confirmPassword = $body['confirmPassword'] ?? null; // Correspond au nom du champ dans le formulaire HTML

        // Validation de base
        if (empty($fullname) || empty($email) || empty($password) || empty($confirmPassword)) {
             Session::flash('error', 'Tous les champs sont requis.');
             header('Location: ' . BASE_URL . '/signup');
             exit;
        }
        if ($password !== $confirmPassword) {
             Session::flash('error', 'Les mots de passe ne correspondent pas.');
             header('Location: ' . BASE_URL . '/signup');
             exit;
        }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            Session::flash('error', 'Format d\'email invalide.');
            header('Location: ' . BASE_URL . '/signup');
            exit;
        }
        // Je cheque si l'email existe
        if ($this->userModel->findByEmail($email)) {
             Session::flash('error', 'L\'adresse email est déjà enregistrée.');
             header('Location: ' . BASE_URL . '/signup');
             exit;
        }

        // Je crée l'utilisateur
        $userData = [
            'fullname' => $fullname,
            'email' => $email,
            'password' => $password,
            // J'ajoute les valeurs par défaut si besoin : 'balance_cad' => 10000.00, 'is_admin' => false
        ];

        if ($this->userModel->create($userData)) {
            Session::flash('success', 'Compte créé avec succès ! Veuillez vous connecter.');
            header('Location: ' . BASE_URL . '/login');
            exit;
        } else {
             Session::flash('error', 'Échec de la création du compte. Veuillez réessayer.');
             header('Location: ' . BASE_URL . '/signup');
             exit;
        }
    }

    public function logout() {
        Csrf::protect($this->request);

        Session::destroy();
        header('Location: ' . BASE_URL . '/login');
        exit;
    }
}
?>