<?php
// /cryptotrade/app/Controllers/AuthController.php
namespace App\Controllers;

use App\Core\Session;
use App\Core\Request;
use App\Models\User;
use PDO;

class AuthController {
    private $db;
    private $request;
    private $userModel;

    public function __construct(PDO $db) {
        $this->db = $db;
        $this->request = new Request(); // Instantiate Request here
        $this->userModel = new User($db);
    }

    // These methods are less relevant now as HTML is served directly
    // public function showLogin() { require 'login.html'; }
    // public function showSignup() { require 'signup.html'; }

    public function login() {
        $body = $this->request->getBody();
        $email = $body['email'] ?? null;
        $password = $body['password'] ?? null;

        if (empty($email) || empty($password)) {
             Session::flash('error', 'Email and password are required.');
             header('Location: ' . BASE_URL . '/login');
             exit;
        }

        $user = $this->userModel->findByEmail($email);

        if ($user && $this->userModel->verifyPassword($password, $user['password_hash'])) {
            if ($user['status'] !== 'active') {
                Session::flash('error', 'Account is inactive.');
                header('Location: ' . BASE_URL . '/login');
                exit;
            }
            // Login successful
            Session::set('user_id', $user['id']);
            Session::set('user_fullname', $user['fullname']);
            Session::set('is_admin', (bool)$user['is_admin']);
            $this->userModel->updateLastLogin($user['id']);

            // Redirect to dashboard
            header('Location: ' . BASE_URL . '/dashboard');
            exit;
        } else {
            // Login failed
            Session::flash('error', 'Invalid email or password.');
            header('Location: ' . BASE_URL . '/login');
            exit;
        }
    }

    public function signup() {
        $body = $this->request->getBody();
        $fullname = $body['fullName'] ?? null; // Match HTML form name
        $email = $body['email'] ?? null;
        $password = $body['password'] ?? null;
        $confirmPassword = $body['confirmPassword'] ?? null; // Match HTML form name

        // Basic Validation
        if (empty($fullname) || empty($email) || empty($password) || empty($confirmPassword)) {
             Session::flash('error', 'All fields are required.');
             header('Location: ' . BASE_URL . '/signup');
             exit;
        }
        if ($password !== $confirmPassword) {
             Session::flash('error', 'Passwords do not match.');
             header('Location: ' . BASE_URL . '/signup');
             exit;
        }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            Session::flash('error', 'Invalid email format.');
            header('Location: ' . BASE_URL . '/signup');
            exit;
        }
        // Check if email exists
        if ($this->userModel->findByEmail($email)) {
             Session::flash('error', 'Email address is already registered.');
             header('Location: ' . BASE_URL . '/signup');
             exit;
        }

        // Create user
        $userData = [
            'fullname' => $fullname,
            'email' => $email,
            'password' => $password,
            // Add defaults if needed: 'balance_cad' => 10000.00, 'is_admin' => false
        ];

        if ($this->userModel->create($userData)) {
            Session::flash('success', 'Account created successfully! Please log in.');
            header('Location: ' . BASE_URL . '/login');
            exit;
        } else {
             Session::flash('error', 'Failed to create account. Please try again.');
             header('Location: ' . BASE_URL . '/signup');
             exit;
        }
    }

    public function logout() {
        Session::destroy();
        header('Location: ' . BASE_URL . '/login');
        exit;
    }
}
?>