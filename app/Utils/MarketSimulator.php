<?php
// /cryptotrade/app/Utils/MarketSimulator.php
namespace App\Utils;

/**
 * Développeur assignés(s) : Moses
 * Entité : Classe 'MarketSimulator' de la couche Utils
 */

class MarketSimulator {
    // Je génère des données historiques plausibles pour ChartJS
    public static function generateHistoricalData($currentPrice, $volatility, $trend, $periods = 30) {
        $historicalData = [];
        $price = $currentPrice;

        // Je pars du prix actuel et je remonte le temps
        for ($i = 0; $i < $periods; $i++) {
             // J'applique la tendance inverse et une volatilité aléatoire
             $randomFactor = 1 + ($volatility * (mt_rand(-100, 100) / 100));
             $trendFactor = 1 - $trend; // Tendance inverse pour les données passées
             $price = $price * $trendFactor * $randomFactor;
             $price = max(0.00001, $price); // Je m'assure que le prix ne descend pas sous zéro

             // J'insère au début pour l'ordre chronologique
             array_unshift($historicalData, round($price, 5));
        }

         // Correct : Je remplace le dernier point calculé par le prix actuel
         // pour que le graphique termine pile au prix actuel.
        if (!empty($historicalData)) {
            $historicalData[$periods - 1] = round($currentPrice, 5);
        } else {
             $historicalData[] = round($currentPrice, 5); // Si seulement 1 période demandée
        }


        // Je génère des labels simples (ex: Jour 1, Jour 2...)
        $labels = [];
        for ($i = 1; $i <= $periods; $i++) {
            // Des labels plus réalistes (dates) demanderaient plus de logique
            $labels[] = "T-" . ($periods - $i); // T-29, T-28 ... T-0
        }
         $labels[$periods-1] = "Maintenant"; // Remplacer "Now" par "Maintenant"


        return [
            'labels' => $labels,
            'prices' => $historicalData
        ];
    }

    // Optionnel : Fonction pour simuler les mises à jour de prix (cron ou manuel)
    // public static function updateCurrentPrices($db) { ... }
}
?>