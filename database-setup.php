<?php
// Database setup page for Railway deployment
// This will create all tables and data automatically via your web app

header('Content-Type: text/html; charset=utf-8');

// Check if running on Railway
if (!isset($_ENV['MYSQL_URL'])) {
    die('This setup page only works on Railway deployment.');
}

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/app/Core/Database.php';
?>
<!DOCTYPE html>
<html>
<head>
    <title>CryptoTrade Database Setup</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 40px; background: #f5f5f5; }
        .container { background: white; padding: 30px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); max-width: 800px; }
        .success { color: #28a745; background: #d4edda; padding: 15px; border-radius: 4px; margin: 10px 0; }
        .error { color: #dc3545; background: #f8d7da; padding: 15px; border-radius: 4px; margin: 10px 0; }
        .info { color: #0c5460; background: #bee5eb; padding: 15px; border-radius: 4px; margin: 10px 0; }
        button { background: #007bff; color: white; padding: 15px 30px; border: none; border-radius: 4px; cursor: pointer; font-size: 16px; margin: 10px 5px; }
        button:hover { background: #0056b3; }
        button:disabled { background: #6c757d; cursor: not-allowed; }
        .step { margin: 20px 0; padding: 20px; border: 1px solid #ddd; border-radius: 4px; }
        .step h3 { margin-top: 0; color: #333; }
        pre { background: #f8f9fa; padding: 15px; border-radius: 4px; overflow-x: auto; font-size: 12px; }
    </style>
</head>
<body>
    <div class="container">
        <h1>🚀 CryptoTrade Database Setup for Railway</h1>
        
        <?php if (isset($_POST['setup_database'])): ?>
            <h2>Setting up your database...</h2>
            
            <?php
            try {
                $db = App\Core\Database::getInstance();
                $conn = $db->getConnection();
                echo "<div class='success'>✅ Database connection successful!</div>";
                
                // Read the database setup SQL
                $sqlFile = __DIR__ . '/railway-db-setup.sql';
                $sql = file_get_contents($sqlFile);
                
                if ($sql === false) {
                    throw new Exception("Could not read railway-db-setup.sql file");
                }
                
                // Execute SQL statements
                $statements = explode(';', $sql);
                $executedCount = 0;
                $errors = [];
                
                foreach ($statements as $statement) {
                    $statement = trim($statement);
                    if (empty($statement) || strpos($statement, '--') === 0) continue;
                    
                    try {
                        $conn->exec($statement);
                        $executedCount++;
                    } catch (PDOException $e) {
                        // Ignore certain expected errors
                        if (strpos($e->getMessage(), 'already exists') === false && 
                            strpos($e->getMessage(), 'Duplicate entry') === false) {
                            $errors[] = $e->getMessage();
                        }
                    }
                }
                
                echo "<div class='success'>✅ Executed $executedCount SQL statements successfully!</div>";
                
                if (!empty($errors)) {
                    echo "<div class='error'>⚠️ Some non-critical errors occurred:<br>" . implode('<br>', array_slice($errors, 0, 3)) . "</div>";
                }
                
                // Verify the setup
                echo "<h3>Database Verification:</h3>";
                
                $stmt = $conn->query("SHOW TABLES");
                $tables = $stmt->fetchAll();
                echo "<div class='info'>📊 Tables created: " . count($tables) . "</div>";
                
                if (count($tables) >= 4) {
                    $stmt = $conn->query("SELECT COUNT(*) as count FROM currencies");
                    $currencies = $stmt->fetch();
                    
                    $stmt = $conn->query("SELECT COUNT(*) as count FROM users");
                    $users = $stmt->fetch();
                    
                    echo "<div class='success'>";
                    echo "💰 Cryptocurrencies: " . $currencies['count'] . "<br>";
                    echo "👥 Test Users: " . $users['count'] . "<br>";
                    echo "✅ Database setup complete!";
                    echo "</div>";
                    
                    echo "<div class='info'>";
                    echo "<strong>Test Accounts Created:</strong><br>";
                    echo "• admin@cryptotrade.com / password123 (Admin - $100,000)<br>";
                    echo "• user@cryptotrade.com / password123 (User - $10,000)<br>";
                    echo "• demo@cryptotrade.com / password123 (Demo - $5,000)";
                    echo "</div>";
                    
                    echo "<div class='success'>";
                    echo "<strong>🎉 Setup Complete!</strong><br>";
                    echo "Your CryptoTrade app is now ready to use!<br>";
                    echo "<a href='/' style='color: white; background: #28a745; padding: 10px 20px; text-decoration: none; border-radius: 4px; display: inline-block; margin-top: 10px;'>Go to CryptoTrade App</a>";
                    echo "</div>";
                }
                
            } catch (Exception $e) {
                echo "<div class='error'>❌ Error: " . htmlspecialchars($e->getMessage()) . "</div>";
            }
            ?>
            
        <?php else: ?>
            
            <div class="step">
                <h3>🔍 Current Status</h3>
                <?php
                try {
                    $db = App\Core\Database::getInstance();
                    $conn = $db->getConnection();
                    echo "<div class='success'>✅ Database connection working!</div>";
                    
                    $stmt = $conn->query("SHOW TABLES");
                    $tables = $stmt->fetchAll();
                    $tableCount = count($tables);
                    
                    if ($tableCount == 0) {
                        echo "<div class='info'>📊 No tables found - ready for setup</div>";
                    } else {
                        echo "<div class='info'>📊 Found $tableCount tables - may need reset or already setup</div>";
                    }
                    
                } catch (Exception $e) {
                    echo "<div class='error'>❌ Database connection failed: " . htmlspecialchars($e->getMessage()) . "</div>";
                }
                ?>
            </div>
            
            <div class="step">
                <h3>🗄️ What This Will Create</h3>
                <ul>
                    <li><strong>4 Database Tables:</strong> users, currencies, wallets, transactions</li>
                    <li><strong>10 Cryptocurrencies:</strong> Bitcoin, Ethereum, Solana, BNB, XRP, Cardano, Dogecoin, Polkadot, Chainlink, Tether</li>
                    <li><strong>3 Test Accounts:</strong> Admin, User, Demo (all with password: password123)</li>
                    <li><strong>Foreign Key Relationships:</strong> Proper database structure for trading</li>
                </ul>
            </div>
            
            <div class="step">
                <h3>🚀 Ready to Setup?</h3>
                <p>Click the button below to automatically create all database tables and initial data.</p>
                <form method="post">
                    <button type="submit" name="setup_database">🔧 Setup Database Now</button>
                </form>
            </div>
            
        <?php endif; ?>
        
        <div style="margin-top: 30px; padding: 15px; background: #f8f9fa; border-radius: 4px; font-size: 12px; color: #666;">
            <strong>Environment Info:</strong><br>
            Database: <?= DB_HOST ?>:<?= DB_PORT ?>/<?= DB_NAME ?><br>
            PHP Version: <?= PHP_VERSION ?><br>
            Railway URL: <?= isset($_ENV['RAILWAY_PUBLIC_DOMAIN']) ? $_ENV['RAILWAY_PUBLIC_DOMAIN'] : 'Not detected' ?>
        </div>
    </div>
</body>
</html>
