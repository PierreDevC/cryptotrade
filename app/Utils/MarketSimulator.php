<?php
// /cryptotrade/app/Utils/MarketSimulator.php
namespace App\Utils;

class MarketSimulator {
    // Generates plausible historical data for ChartJS
    public static function generateHistoricalData($currentPrice, $volatility, $trend, $periods = 30) {
        $historicalData = [];
        $price = $currentPrice;

        // Work backwards from current price
        for ($i = 0; $i < $periods; $i++) {
             // Apply reverse trend and random volatility
             $randomFactor = 1 + ($volatility * (mt_rand(-100, 100) / 100));
             $trendFactor = 1 - $trend; // Reverse trend for past data
             $price = $price * $trendFactor * $randomFactor;
             $price = max(0.00001, $price); // Ensure price doesn't go below zero

             // Insert at the beginning to get chronological order
             array_unshift($historicalData, round($price, 5));
        }

         // Add the actual current price as the last point
        // array_push($historicalData, round($currentPrice, 5));
        // Correct: Replace the last calculated point with the actual current price
        // to ensure the chart ends exactly at the current value.
        if (!empty($historicalData)) {
            $historicalData[$periods - 1] = round($currentPrice, 5);
        } else {
             $historicalData[] = round($currentPrice, 5); // If only 1 period requested
        }


        // Generate simple labels (e.g., Day 1, Day 2...)
        $labels = [];
        for ($i = 1; $i <= $periods; $i++) {
            // More realistic labels (e.g., dates) would require more logic
            $labels[] = "T-" . ($periods - $i); // T-29, T-28 ... T-0
        }
         $labels[$periods-1] = "Now";


        return [
            'labels' => $labels,
            'prices' => $historicalData
        ];
    }

    // Optional: Function to simulate live price updates (run via cron or manually)
    // public static function updateCurrentPrices($db) { ... }
}
?>