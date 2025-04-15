<?php
// /cryptotrade/app/Controllers/UserController.php
namespace App\Controllers;

use App\Core\Session;
use App\Core\Request;
use App\Utils\AuthGuard;
use App\Models\User;
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

    // API Endpoint: Handle Profile Update Request
    public function updateProfile() {
        AuthGuard::protect(); // User must be logged in
        $userId = AuthGuard::user();
        $body = $this->request->getBody();

        // Extract data
        $fullname = trim($body['fullname'] ?? '');
        $email = trim($body['email'] ?? '');
        $currentPassword = $body['currentPassword'] ?? '';
        $newPassword = $body['newPassword'] ?? '';
        $confirmPassword = $body['confirmPassword'] ?? '';

        $profileUpdated = false;
        $passwordUpdated = false;
        $updateMessages = [];

        // --- Validate basic fields ---
        if (empty($fullname) || empty($email)) {
            return $this->jsonResponse(false, 'Full name and email are required.', null, 400);
        }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return $this->jsonResponse(false, 'Invalid email format.', null, 400);
        }

        // --- Update Profile Info (Fullname & Email) ---
        try {
            $currentUser = $this->userModel->findById($userId);
            // Only update if different to avoid unnecessary DB call / email check
            if ($currentUser['fullname'] !== $fullname || $currentUser['email'] !== $email) {
                $updateResult = $this->userModel->updateProfile($userId, $fullname, $email);
                if ($updateResult) {
                    $profileUpdated = true;
                    $updateMessages[] = 'Profile details updated successfully.';
                    // Update session fullname if changed
                    if ($currentUser['fullname'] !== $fullname) {
                         Session::set('user_fullname', $fullname);
                    }
                } else {
                    // updateProfile returns false likely due to email conflict
                    return $this->jsonResponse(false, 'Email address is already in use by another account.', null, 409); // Conflict
                }
            }
        } catch (\PDOException $e) {
            error_log("DB Error updating profile for user {$userId}: " . $e->getMessage());
            return $this->jsonResponse(false, 'Database error updating profile.', null, 500);
        }

        // --- Update Password (if new password provided) ---
        if (!empty($newPassword)) {
            if (empty($currentPassword)) {
                return $this->jsonResponse(false, 'Current password is required to set a new one.', null, 400);
            }
            if ($newPassword !== $confirmPassword) {
                 return $this->jsonResponse(false, 'New passwords do not match.', null, 400);
            }
            // Basic password length validation (optional)
            if (strlen($newPassword) < 6) { // Example minimum length
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
                         return $this->jsonResponse(false, 'The current password you entered is incorrect.', null, 403); // Forbidden
                    case 'update_failed':
                        throw new \Exception('Password update failed in model.'); // Treat as server error
                    case 'user_not_found': // Should not happen if AuthGuard worked
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

        // --- Final Response ---
        if ($profileUpdated || $passwordUpdated) {
             // Return specific data if needed by frontend (e.g., new fullname)
            $responseData = ['fullname' => $fullname];
            return $this->jsonResponse(true, implode(' ', $updateMessages), $responseData);
        } else {
            // No changes were made
            return $this->jsonResponse(true, 'No changes detected.');
        }
    }

    // API Endpoint: Get Transaction History for the logged-in user
    public function getUserTransactions() {
        AuthGuard::protect();
        $userId = AuthGuard::user();

        $transactionModel = new \App\Models\Transaction($this->db);
        $transactions = $transactionModel->findAllByUser($userId);

        // Format data for display
        $formattedTransactions = [];
        $usdToCadRate = 1.35; // TODO: Get from config

        foreach($transactions as $tx) {
            // Determine the sign for display based on transaction type
            $amountSign = ($tx['type'] == 'buy') ? '-' : '+';
            $quantitySign = ($tx['type'] == 'buy') ? '+' : '-';

             $formattedTransactions[] = [
                  'id' => $tx['id'],
                  'timestamp' => date('Y-m-d H:i:s', strtotime($tx['timestamp'])),
                  'type' => ucfirst($tx['type']), // 'Buy' or 'Sell'
                  'currency_name' => $tx['currency_name'],
                  'currency_symbol' => $tx['currency_symbol'],
                  'quantity' => number_format((float)$tx['quantity'], 8, '.', ''),
                  //'quantity_display' => $quantitySign . number_format((float)$tx['quantity'], 8, '.', ''),
                  'price_per_unit_usd' => number_format((float)$tx['price_per_unit_usd'], 2, '.', ','),
                  'total_amount_cad' => number_format((float)$tx['total_amount_cad'], 2, '.', ','),
                  'total_amount_cad_display' => $amountSign . number_format((float)$tx['total_amount_cad'], 2, '.', ',') . '$',
                  'type_class' => ($tx['type'] == 'buy') ? 'text-danger' : 'text-success' // Class for styling
             ];
        }

        $this->jsonResponse(true, 'Transactions retrieved successfully.', $formattedTransactions);
    }

    // Download Transaction History as CSV
    public function downloadTransactionsCsv() {
        AuthGuard::protect();
        $userId = AuthGuard::user();

        $transactionModel = new \App\Models\Transaction($this->db);
        $transactions = $transactionModel->findAllByUser($userId);

        // Set headers for CSV download
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename=transactions-' . date('Y-m-d') . '.csv');

        // Create a file pointer connected to the output stream
        $output = fopen('php://output', 'w');

        // Output the CSV header row
        fputcsv($output, ['Date', 'Type', 'Crypto Name', 'Symbol', 'Quantity', 'Price Per Unit (USD)', 'Total Amount (CAD)']);

        // Output data rows
        foreach ($transactions as $tx) {
            fputcsv($output, [
                date('Y-m-d H:i:s', strtotime($tx['timestamp'])),
                ucfirst($tx['type']),
                $tx['currency_name'],
                $tx['currency_symbol'],
                number_format((float)$tx['quantity'], 8, '.', ''), // Raw quantity
                number_format((float)$tx['price_per_unit_usd'], 2, '.', ''), // Raw price
                number_format((float)$tx['total_amount_cad'], 2, '.', '') // Raw amount
            ]);
        }

        fclose($output);
        exit; // Stop script execution after generating CSV
    }

    // Download Transaction History as PDF (Requires a PDF library like TCPDF)
    public function downloadTransactionsPdf() {
        AuthGuard::protect();
        $userId = AuthGuard::user();

        // Check if a PDF library class exists (e.g., TCPDF)
        if (!class_exists('TCPDF')) {
            // You could render an error page or return a JSON error
            http_response_code(501); // Not Implemented
            echo "Erreur: La bibliothèque PDF (TCPDF) n'est pas installée ou configurée.";
            error_log("Attempted PDF download without TCPDF library.");
            exit;
        }

        $transactionModel = new \App\Models\Transaction($this->db);
        $transactions = $transactionModel->findAllByUser($userId);

        // --- PDF Generation Logic (Example using TCPDF) ---
        // $pdf = new \TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);

        // Set document information
        // $pdf->SetCreator(PDF_CREATOR);
        // $pdf->SetAuthor('CryptoTrade');
        // $pdf->SetTitle('Historique des Transactions');
        // $pdf->SetSubject('Transactions Utilisateur ' . $userId);

        // Set default header/footer data (optional)
        // $pdf->setHeaderFont(Array(PDF_FONT_NAME_MAIN, '', PDF_FONT_SIZE_MAIN));
        // $pdf->setFooterFont(Array(PDF_FONT_NAME_DATA, '', PDF_FONT_SIZE_DATA));

        // Set margins
        // $pdf->SetMargins(PDF_MARGIN_LEFT, PDF_MARGIN_TOP, PDF_MARGIN_RIGHT);
        // $pdf->SetHeaderMargin(PDF_MARGIN_HEADER);
        // $pdf->SetFooterMargin(PDF_MARGIN_FOOTER);

        // Set auto page breaks
        // $pdf->SetAutoPageBreak(TRUE, PDF_MARGIN_BOTTOM);

        // Add a page
        // $pdf->AddPage();

        // Set font
        // $pdf->SetFont('helvetica', '', 10);

        // Title
        // $pdf->Cell(0, 10, 'Historique des Transactions - Utilisateur ' . $userId, 0, 1, 'C');

        // Table Header
        // $header = ['Date', 'Type', 'Crypto', 'Qté', 'Prix/U (USD)', 'Total (CAD)'];
        // $w = [35, 15, 40, 30, 30, 30]; // Column widths
        // $pdf->SetFillColor(224, 235, 255);
        // $pdf->SetTextColor(0);
        // $pdf->SetDrawColor(128, 0, 0);
        // $pdf->SetLineWidth(0.3);
        // $pdf->SetFont('', 'B');
        // for($i = 0; $i < count($header); $i++)
        //     $pdf->Cell($w[$i], 7, $header[$i], 1, 0, 'C', 1);
        // $pdf->Ln();

        // Table Data
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
        // $pdf->Cell(array_sum($w), 0, '', 'T'); // Closing line

        // --- End PDF Generation Logic ---

        // Set headers and output the PDF
        // header('Content-Type: application/pdf');
        // header('Content-Disposition: attachment; filename="transactions-' . date('Y-m-d') . '.pdf"');
        // header('Cache-Control: private, max-age=0, must-revalidate');
        // header('Pragma: public');
        // $pdf->Output('transactions-' . date('Y-m-d') . '.pdf', 'D'); // D = Force Download
        // exit;

        // Placeholder until library is implemented:
        http_response_code(501); // Not Implemented
        echo "Fonctionnalité PDF non implémentée. Requiert une bibliothèque PDF côté serveur.";
        exit;
    }

    // Helper to send JSON responses
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