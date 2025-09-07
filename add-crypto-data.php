<?php
// Script to add cryptocurrency seed data to existing database
// This can be used to add more cryptocurrencies without recreating the entire database

require_once "config.php";
require_once "app/Core/Database.php";

echo "<h1>Add Cryptocurrency Data</h1>";

try {
    $db = App\Core\Database::getInstance()->getConnection();
    
    // Check if currencies table exists
    $stmt = $db->query("SHOW TABLES LIKE 'currencies'");
    if ($stmt->rowCount() === 0) {
        echo "<p style='color: red;'>❌ Currencies table does not exist. Please run database-setup.php first.</p>";
        exit;
    }
    
    // Check current cryptocurrency count
    $stmt = $db->query("SELECT COUNT(*) as count FROM currencies");
    $currentCount = $stmt->fetch()['count'];
    echo "<p>Current cryptocurrencies in database: <strong>{$currentCount}</strong></p>";
    
    // Define the cryptocurrency data
    $cryptocurrencies = [
        ['Bitcoin', 'BTC', 67500.00, 1.85, 1330000000000.00, 0.0150, 0.0008],
        ['Ethereum', 'ETH', 3550.00, -0.50, 426000000000.00, 0.0210, 0.0012],
        ['Solana', 'SOL', 150.00, 4.20, 69000000000.00, 0.0380, 0.0015],
        ['BNB', 'BNB', 610.00, 0.90, 90000000000.00, 0.0250, 0.0010],
        ['XRP', 'XRP', 0.5200, -1.10, 28000000000.00, 0.0300, 0.0005],
        ['Cardano', 'ADA', 0.4500, 2.10, 16000000000.00, 0.0320, 0.0007],
        ['Dogecoin', 'DOGE', 0.1600, 5.50, 23000000000.00, 0.0500, 0.0003],
        ['Polkadot', 'DOT', 6.50, 1.50, 9000000000.00, 0.0360, 0.0009],
        ['Chainlink', 'LINK', 15.80, 0.75, 8500000000.00, 0.0280, 0.0006],
        ['Tether', 'USDT', 1.0000, 0.01, 83000000000.00, 0.0050, 0.0001],
        ['Avalanche', 'AVAX', 28.50, 3.20, 11000000000.00, 0.0420, 0.0011],
        ['Polygon', 'MATIC', 0.8900, -2.15, 8200000000.00, 0.0380, 0.0004],
        ['Litecoin', 'LTC', 85.40, 1.30, 6300000000.00, 0.0290, 0.0007],
        ['Uniswap', 'UNI', 7.20, 2.80, 4300000000.00, 0.0350, 0.0008],
        ['Cosmos', 'ATOM', 9.80, -1.50, 3800000000.00, 0.0330, 0.0005]
    ];
    
    echo "<h2>Adding Cryptocurrency Data</h2>";
    echo "<ul>";
    
    $addedCount = 0;
    $skippedCount = 0;
    
    // Prepare the insert statement
    $insertStmt = $db->prepare("
        INSERT INTO currencies (name, symbol, current_price_usd, change_24h_percent, market_cap_usd, base_volatility, base_trend) 
        VALUES (?, ?, ?, ?, ?, ?, ?)
    ");
    
    // Check if symbol exists statement
    $checkStmt = $db->prepare("SELECT COUNT(*) FROM currencies WHERE symbol = ?");
    
    foreach ($cryptocurrencies as $crypto) {
        list($name, $symbol, $price, $change, $marketCap, $volatility, $trend) = $crypto;
        
        // Check if this cryptocurrency already exists
        $checkStmt->execute([$symbol]);
        $exists = $checkStmt->fetchColumn() > 0;
        
        if ($exists) {
            echo "<li style='color: orange;'>⚠️ {$name} ({$symbol}) already exists - skipped</li>";
            $skippedCount++;
        } else {
            try {
                $insertStmt->execute([$name, $symbol, $price, $change, $marketCap, $volatility, $trend]);
                echo "<li style='color: green;'>✅ Added {$name} ({$symbol}) - \${$price}</li>";
                $addedCount++;
            } catch (PDOException $e) {
                echo "<li style='color: red;'>❌ Failed to add {$name}: " . htmlspecialchars($e->getMessage()) . "</li>";
            }
        }
    }
    
    echo "</ul>";
    
    // Final count
    $stmt = $db->query("SELECT COUNT(*) as count FROM currencies");
    $finalCount = $stmt->fetch()['count'];
    
    echo "<h2>Summary</h2>";
    echo "<p><strong>Added:</strong> {$addedCount} cryptocurrencies</p>";
    echo "<p><strong>Skipped:</strong> {$skippedCount} (already existed)</p>";
    echo "<p><strong>Total cryptocurrencies:</strong> {$finalCount}</p>";
    
    if ($addedCount > 0) {
        echo "<div style='background: #d4edda; border: 1px solid #c3e6cb; color: #155724; padding: 15px; border-radius: 5px; margin: 20px 0;'>";
        echo "<h3>✅ Cryptocurrency Data Added Successfully!</h3>";
        echo "<p>Your database now has {$finalCount} cryptocurrencies available for trading.</p>";
        echo "</div>";
    } else {
        echo "<div style='background: #fff3cd; border: 1px solid #ffeaa7; color: #856404; padding: 15px; border-radius: 5px; margin: 20px 0;'>";
        echo "<h3>ℹ️ No New Cryptocurrencies Added</h3>";
        echo "<p>All cryptocurrencies already exist in the database.</p>";
        echo "</div>";
    }
    
    // Show current cryptocurrencies
    echo "<h2>Current Cryptocurrencies</h2>";
    $stmt = $db->query("SELECT name, symbol, current_price_usd, market_cap_usd FROM currencies ORDER BY market_cap_usd DESC");
    $cryptos = $stmt->fetchAll();
    
    echo "<table border='1' style='border-collapse: collapse; width: 100%; margin: 20px 0;'>";
    echo "<tr style='background: #f8f9fa;'><th>Name</th><th>Symbol</th><th>Price (USD)</th><th>Market Cap (USD)</th></tr>";
    
    foreach ($cryptos as $crypto) {
        $price = number_format($crypto['current_price_usd'], 4);
        $marketCap = number_format($crypto['market_cap_usd'] / 1000000000, 2) . 'B';
        echo "<tr>";
        echo "<td>{$crypto['name']}</td>";
        echo "<td><strong>{$crypto['symbol']}</strong></td>";
        echo "<td>\${$price}</td>";
        echo "<td>\${$marketCap}</td>";
        echo "</tr>";
    }
    echo "</table>";
    
} catch (Exception $e) {
    echo "<div style='background: #f8d7da; border: 1px solid #f5c6cb; color: #721c24; padding: 15px; border-radius: 5px; margin: 20px 0;'>";
    echo "<h3>❌ Error Occurred</h3>";
    echo "<p>Error: " . htmlspecialchars($e->getMessage()) . "</p>";
    echo "</div>";
}
?>

<style>
body {
    font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
    max-width: 900px;
    margin: 0 auto;
    padding: 20px;
    line-height: 1.6;
}
h1, h2, h3 {
    color: #333;
}
table {
    width: 100%;
}
th, td {
    padding: 8px 12px;
    text-align: left;
    border: 1px solid #ddd;
}
th {
    background: #f8f9fa;
    font-weight: bold;
}
ul {
    background: #f8f9fa;
    padding: 20px;
    border-radius: 5px;
    max-height: 400px;
    overflow-y: auto;
}
</style>
