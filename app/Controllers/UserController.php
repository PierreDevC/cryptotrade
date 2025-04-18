<?php
// /cryptotrade/app/Controllers/UserController.php
namespace App\Controllers;

/**
 * Développeur assignés(s) : Pierre
 * Entité : Classe 'UserController' de la couche Controllers
 */

use App\Core\Session;
use App\Core\Request;
use App\Utils\AuthGuard;
use App\Models\User;
use App\Utils\Csrf;
use PDO;

class UserController {
    private $db;
    private $request;
    private $userModel;

    public function __construct(PDO $db) {
        $this->db = $db;
        $this->request = new Request();
        $this->userModel = new User($db);
    }

    // Point API: MAJ Profil
    public function updateProfile() {
        AuthGuard::protect(); // Faut être connecté
        Csrf::protect($this->request);

        $userId = AuthGuard::user();
        $body = $this->request->getBody();

        // Je récupère les infos
        $fullname = trim($body['fullname'] ?? '');
        $email = trim($body['email'] ?? '');
        $currentPassword = $body['currentPassword'] ?? '';
        $newPassword = $body['newPassword'] ?? '';
        $confirmPassword = $body['confirmPassword'] ?? '';

        $profileUpdated = false;
        $passwordUpdated = false;
        $updateMessages = [];

        // --- Validations de base ---
        if (empty($fullname) || empty($email)) {
            return $this->jsonResponse(false, 'Full name and email are required.', null, 400);
        }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return $this->jsonResponse(false, 'Invalid email format.', null, 400);
        }

        // --- MAJ Infos Profil (Nom & Email) ---
        try {
            $currentUser = $this->userModel->findById($userId);
            // Je MAJ que si c'est différent (perf BDD / check email)
            if ($currentUser['fullname'] !== $fullname || $currentUser['email'] !== $email) {
                $updateResult = $this->userModel->updateProfile($userId, $fullname, $email);
                if ($updateResult) {
                    $profileUpdated = true;
                    $updateMessages[] = 'Profile details updated successfully.';
                    // MAJ session si nom changé
                    if ($currentUser['fullname'] !== $fullname) {
                         Session::set('user_fullname', $fullname);
                    }
                } else {
                    // updateProfile false = conflit email
                    return $this->jsonResponse(false, 'Email address is already in use by another account.', null, 409); // Conflit
                }
            }
        } catch (\PDOException $e) {
            error_log("DB Error updating profile for user {$userId}: " . $e->getMessage());
            return $this->jsonResponse(false, 'Database error updating profile.', null, 500);
        }

        // --- MAJ Mot de passe (si nouveau fourni) ---
        if (!empty($newPassword)) {
            if (empty($currentPassword)) {
                return $this->jsonResponse(false, 'Current password is required to set a new one.', null, 400);
            }
            if ($newPassword !== $confirmPassword) {
                 return $this->jsonResponse(false, 'New passwords do not match.', null, 400);
            }
            // Valid longueur mdp (optionnel)
            if (strlen($newPassword) < 6) { // Ex: min 6 chars
                 return $this->jsonResponse(false, 'New password must be at least 6 characters long.', null, 400);
            }

            try {
                $passwordResult = $this->userModel->updatePassword($userId, $currentPassword, $newPassword);
                switch ($passwordResult) {
                    case 'success':
                        $passwordUpdated = true;
                        $updateMessages[] = 'Password updated successfully.';
                        break;
                    case 'invalid_current_password':
                         return $this->jsonResponse(false, 'The current password you entered is incorrect.', null, 403); // Interdit
                    case 'update_failed':
                        throw new \Exception('Password update failed in model.'); // Erreur serveur
                    case 'user_not_found': // Devrait pas arriver avec AuthGuard
                         error_log("User {$userId} not found during password update despite being logged in.");
                         return $this->jsonResponse(false, 'User not found.', null, 404);
                }
            } catch (\PDOException $e) {
                error_log("DB Error updating password for user {$userId}: " . $e->getMessage());
                 return $this->jsonResponse(false, 'Database error updating password.', null, 500);
            } catch (\Exception $e) {
                 error_log("General Error updating password for user {$userId}: " . $e->getMessage());
                 return $this->jsonResponse(false, 'Error updating password.', null, 500);
            }
        }

        // --- Réponse Finale ---
        if ($profileUpdated || $passwordUpdated) {
             // Je retourne data si besoin (ex: nouveau nom)
            $responseData = ['fullname' => $fullname];
            return $this->jsonResponse(true, implode(' ', $updateMessages), $responseData);
        } else {
            // Rien n'a changé
            return $this->jsonResponse(true, 'No changes detected.');
        }
    }

    // Point API: Historique transactions user co
    public function getUserTransactions() {
        AuthGuard::protect();
        $userId = AuthGuard::user();

        $transactionModel = new \App\Models\Transaction($this->db);
        $transactions = $transactionModel->findAllByUser($userId);

        // Formatage pour affichage
        $formattedTransactions = [];
        $usdToCadRate = 1.35; // TODO: Prendre de la config

        foreach($transactions as $tx) {
            // Signe pour affichage (+/-)
            $amountSign = ($tx['type'] == 'buy') ? '-' : '+';
            $quantitySign = ($tx['type'] == 'buy') ? '+' : '-';

             $formattedTransactions[] = [
                  'id' => $tx['id'],
                  'timestamp' => date('Y-m-d H:i:s', strtotime($tx['timestamp'])),
                  'type' => ucfirst($tx['type']), // 'Buy' ou 'Sell'
                  'currency_name' => $tx['currency_name'],
                  'currency_symbol' => $tx['currency_symbol'],
                  'quantity' => number_format((float)$tx['quantity'], 8, '.', ''),
                  // Prix CAD brut (colonne _usd) pour JS
                  'price_per_unit_cad' => (float)$tx['price_per_unit_usd'],
                  'total_amount_cad' => number_format((float)$tx['total_amount_cad'], 2, '.', ','),
                  'total_amount_cad_display' => $amountSign . number_format((float)$tx['total_amount_cad'], 2, '.', ',') . '$',
                  'type_class' => ($tx['type'] == 'buy') ? 'text-danger' : 'text-success' // Classe pour style
             ];
        }

        $this->jsonResponse(true, 'Transactions retrieved successfully.', $formattedTransactions);
    }

    // Télécharger historique en CSV
    public function downloadTransactionsCsv() {
        AuthGuard::protect();
        $userId = AuthGuard::user();

        $transactionModel = new \App\Models\Transaction($this->db);
        $transactions = $transactionModel->findAllByUser($userId);

        // Headers pour dl CSV
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename=transactions-' . date('Y-m-d') . '.csv');

        // Pointeur fichier vers sortie
        $output = fopen('php://output', 'w');

        // Entête CSV
        fputcsv($output, ['Date', 'Type', 'Crypto Name', 'Symbol', 'Quantity', 'Price Per Unit (CAD)', 'Total Amount (CAD)']);

        // Lignes de données
        foreach ($transactions as $tx) {
            fputcsv($output, [
                date('Y-m-d H:i:s', strtotime($tx['timestamp'])),
                ucfirst($tx['type']),
                $tx['currency_name'],
                $tx['currency_symbol'],
                number_format((float)$tx['quantity'], 8, '.', ''), // Quantité brute
                number_format((float)$tx['price_per_unit_usd'], 2, '.', ''), // Prix brut (représente CAD)
                number_format((float)$tx['total_amount_cad'], 2, '.', '') // Montant brut
            ]);
        }

        fclose($output);
        exit; // Stop après générer CSV
    }

    // Télécharger historique en PDF (Besoin lib PDF genre TCPDF)
    public function downloadTransactionsPdf() {
        AuthGuard::protect();
        $userId = AuthGuard::user();

        // Check si lib PDF existe (ex: TCPDF)
        if (!class_exists('TCPDF')) {
            // Erreur JSON ou page d'erreur
            http_response_code(501); // Pas implémenté
            echo "Erreur: La bibliothèque PDF (TCPDF) n'est pas installée ou configurée.";
            error_log("Attempted PDF download without TCPDF library.");
            exit;
        }

        $transactionModel = new \App\Models\Transaction($this->db);
        $transactions = $transactionModel->findAllByUser($userId);

        // --- Logique Génération PDF (Exemple TCPDF) ---
        // $pdf = new \TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);

        // Infos document
        // $pdf->SetCreator(PDF_CREATOR);
        // $pdf->SetAuthor('CryptoTrade');
        // $pdf->SetTitle('Historique des Transactions');
        // $pdf->SetSubject('Transactions Utilisateur ' . $userId);

        // Header/footer par défaut (optionnel)
        // $pdf->setHeaderFont(Array(PDF_FONT_NAME_MAIN, '', PDF_FONT_SIZE_MAIN));
        // $pdf->setFooterFont(Array(PDF_FONT_NAME_DATA, '', PDF_FONT_SIZE_DATA));

        // Marges
        // $pdf->SetMargins(PDF_MARGIN_LEFT, PDF_MARGIN_TOP, PDF_MARGIN_RIGHT);
        // $pdf->SetHeaderMargin(PDF_MARGIN_HEADER);
        // $pdf->SetFooterMargin(PDF_MARGIN_FOOTER);

        // Saut page auto
        // $pdf->SetAutoPageBreak(TRUE, PDF_MARGIN_BOTTOM);

        // Ajouter page
        // $pdf->AddPage();

        // Police
        // $pdf->SetFont('helvetica', '', 10);

        // Titre
        // $pdf->Cell(0, 10, 'Historique des Transactions - Utilisateur ' . $userId, 0, 1, 'C');

        // Entête Tableau
        // $header = ['Date', 'Type', 'Crypto', 'Qté', 'Prix/U (USD)', 'Total (CAD)'];
        // $w = [35, 15, 40, 30, 30, 30]; // Largeurs colonnes
        // $pdf->SetFillColor(224, 235, 255);
        // $pdf->SetTextColor(0);
        // $pdf->SetDrawColor(128, 0, 0);
        // $pdf->SetLineWidth(0.3);
        // $pdf->SetFont('', 'B');
        // for($i = 0; $i < count($header); $i++)
        //     $pdf->Cell($w[$i], 7, $header[$i], 1, 0, 'C', 1);
        // $pdf->Ln();

        // Données Tableau
        // $pdf->SetFillColor(245, 245, 245);
        // $pdf->SetTextColor(0);
        // $pdf->SetFont('');
        // $fill = 0;
        // foreach($transactions as $tx) {
        //     $pdf->Cell($w[0], 6, date('Y-m-d H:i', strtotime($tx['timestamp'])), 'LR', 0, 'L', $fill);
        //     $pdf->Cell($w[1], 6, ucfirst($tx['type']), 'LR', 0, 'L', $fill);
        //     $pdf->Cell($w[2], 6, $tx['currency_symbol'], 'LR', 0, 'L', $fill);
        //     $pdf->Cell($w[3], 6, number_format((float)$tx['quantity'], 6), 'LR', 0, 'R', $fill);
        //     $pdf->Cell($w[4], 6, number_format((float)$tx['price_per_unit_usd'], 2), 'LR', 0, 'R', $fill);
        //     $pdf->Cell($w[5], 6, number_format((float)$tx['total_amount_cad'], 2), 'LR', 0, 'R', $fill);
        //     $pdf->Ln();
        //     $fill=!$fill;
        // }
        // $pdf->Cell(array_sum($w), 0, '', 'T'); // Ligne fin

        // --- Fin Logique Génération PDF ---

        // Headers et sortie PDF
        // header('Content-Type: application/pdf');
        // header('Content-Disposition: attachment; filename="transactions-' . date('Y-m-d') . '.pdf"');
        // header('Cache-Control: private, max-age=0, must-revalidate');
        // header('Pragma: public');
        // $pdf->Output('transactions-' . date('Y-m-d') . '.pdf', 'D'); // D = Force Download
        // exit;

        // Temporaire jusqu'à implémentation lib:
        http_response_code(501); // Pas implémenté
        echo "Fonctionnalité PDF non implémentée. Requiert une bibliothèque PDF côté serveur.";
        exit;
    }

    // Aide pour réponses JSON
    private function jsonResponse($success, $message = '', $data = null, $statusCode = null) {
        header('Content-Type: application/json');
        if ($statusCode === null) {
            $statusCode = $success ? 200 : 400;
        }
        http_response_code($statusCode);
        $response = ['success' => $success, 'message' => $message];
        if ($data !== null) {
            $response['data'] = $data;
        }
        echo json_encode($response);
        exit;
    }
}
?> 