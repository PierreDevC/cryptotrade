<?php
// /cryptotrade/app/Utils/Logger.php
namespace App\Utils;
use App\Core\Session; // Pour récupérer l'ID utilisateur

/**
 * Développeur assignés(s) : Seydina
 * Entité : Classe 'Logger' de la couche Utils
 */

class Logger {
    // Le chemin vers mon fichier de log (relatif à ce fichier)
    private static $logFilePath = __DIR__ . '/../../storage/logs/audit.log';

    /**
     * J'enregistre une action dans le fichier de log.
     *
     * @param string $action Description courte (ex: 'login_success', 'buy_crypto').
     * @param array $details Détails optionnels (ex: ['currency_id' => 5, 'quantity' => 0.1]).
     */
    public static function logAction(string $action, array $details = []) {
        // Je vérifie que le dossier de logs existe
        $logDir = dirname(self::$logFilePath);
        if (!is_dir($logDir)) {
            // J'essaie de le créer si besoin (récursivement)
            // @ pour ignorer les erreurs de création (permissions)
            @mkdir($logDir, 0775, true);
        }

        // Je récupère le contexte
        $timestamp = date('Y-m-d H:i:s');
        $userId = Session::get('user_id') ?? 'Guest'; // 'Guest' si pas de session
        $ipAddress = $_SERVER['REMOTE_ADDR'] ?? 'Unknown IP';
        $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown User Agent';

        // Je formate les détails (en JSON c'est bien)
        $detailsString = !empty($details) ? json_encode($details) : '-';

        // Je prépare le message de log
        $logMessage = sprintf(
            "[%s] UserID: %s | IP: %s | Action: %s | Details: %s | UserAgent: %s%s",
            $timestamp,
            $userId,
            $ipAddress,
            $action,
            $detailsString,
            $userAgent,
            PHP_EOL // Nouvelle ligne pour chaque entrée
        );

        // J'ajoute au fichier de log (avec verrouillage exclusif)
        // @ pour ignorer les erreurs d'écriture (permissions)
        // En prod, faudrait mieux gérer les erreurs
        $writeSuccess = @file_put_contents(self::$logFilePath, $logMessage, FILE_APPEND | LOCK_EX);

        // Optionnel : je log une erreur si l'écriture a échoué (ex: dans les logs PHP)
        if ($writeSuccess === false) {
            error_log("Logger Error: Failed to write to audit log file: " . self::$logFilePath . " - Check permissions.");
        }
    }
}
?> 