<?php
// Database Setup Script for Railway
// This script initializes the database with tables and sample data
// Run this ONCE after deploying to Railway

require_once "config.php";
require_once "app/Core/Database.php";

// Only allow this to run if we're on Railway or if explicitly allowed
if (!isset($_ENV['RAILWAY_PUBLIC_DOMAIN']) && !isset($_GET['allow_local'])) {
    die("This script is only meant for Railway deployment. Add ?allow_local=1 to run locally.");
}

try {
    $db = App\Core\Database::getInstance()->getConnection();
    
    echo "<h1>CryptoTrade Database Setup</h1>";
    echo "<p>Setting up database tables and initial data...</p>";
    
    // Read and execute the setup SQL
    $setupSQL = file_get_contents(__DIR__ . '/railway-db-setup.sql');
    
    if (!$setupSQL) {
        throw new Exception("Could not read railway-db-setup.sql file");
    }
    
    // Split SQL into individual statements and clean them
    $statements = array_filter(array_map('trim', explode(';', $setupSQL)));
    
    $successCount = 0;
    $errorCount = 0;
    
    echo "<h2>Executing SQL Statements:</h2>";
    echo "<ul>";
    
    foreach ($statements as $statement) {
        if (empty($statement) || strpos($statement, '--') === 0 || strpos($statement, 'SELECT') === 0) {
            continue; // Skip empty lines, comments, and SELECT statements
        }
        
        try {
            // Use prepare and execute for better error handling
            $stmt = $db->prepare($statement);
            $result = $stmt->execute();
            
            if ($result) {
                $successCount++;
                
                // Show what we're doing
                if (stripos($statement, 'CREATE TABLE') !== false) {
                    preg_match('/CREATE TABLE `?(\w+)`?/i', $statement, $matches);
                    $tableName = $matches[1] ?? 'unknown';
                    echo "<li style='color: green;'>✓ Created table: {$tableName}</li>";
                } elseif (stripos($statement, 'INSERT INTO') !== false) {
                    preg_match('/INSERT INTO `?(\w+)`?/i', $statement, $matches);
                    $tableName = $matches[1] ?? 'unknown';
                    $rowCount = $stmt->rowCount();
                    echo "<li style='color: blue;'>✓ Inserted {$rowCount} rows into: {$tableName}</li>";
                } elseif (stripos($statement, 'DROP TABLE') !== false) {
                    preg_match('/DROP TABLE.*?`?(\w+)`?/i', $statement, $matches);
                    $tableName = $matches[1] ?? 'unknown';
                    echo "<li style='color: orange;'>✓ Dropped table: {$tableName}</li>";
                } else {
                    echo "<li style='color: gray;'>✓ Executed statement</li>";
                }
            } else {
                $errorCount++;
                echo "<li style='color: red;'>✗ Failed to execute statement</li>";
            }
            
            // Close the statement to free up resources
            $stmt->closeCursor();
            
        } catch (PDOException $e) {
            $errorCount++;
            echo "<li style='color: red;'>✗ Error: " . htmlspecialchars($e->getMessage()) . "</li>";
        }
    }
    
    echo "</ul>";
    
    echo "<h2>Setup Summary:</h2>";
    echo "<p><strong>Successful operations:</strong> {$successCount}</p>";
    echo "<p><strong>Errors:</strong> {$errorCount}</p>";
    
    if ($errorCount === 0) {
        echo "<div style='background: #d4edda; border: 1px solid #c3e6cb; color: #155724; padding: 15px; border-radius: 5px; margin: 20px 0;'>";
        echo "<h3>✅ Database Setup Complete!</h3>";
        echo "<p>Your CryptoTrade database has been successfully initialized.</p>";
        echo "<h4>Test Admin Account:</h4>";
        echo "<ul>";
        echo "<li><strong>Email:</strong> admin@cryptotrade.com</li>";
        echo "<li><strong>Password:</strong> password123</li>";
        echo "</ul>";
        echo "<p><a href='" . BASE_URL . "/login' style='background: #007bff; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>Go to Login Page</a></p>";
        echo "</div>";
        
        // Security: Delete this file after successful setup
        echo "<h3>Security Notice:</h3>";
        echo "<p style='color: #856404; background: #fff3cd; border: 1px solid #ffeaa7; padding: 10px; border-radius: 5px;'>";
        echo "⚠️ For security, you should delete this setup file after use:<br>";
        echo "<code>rm database-setup.php</code>";
        echo "</p>";
        
    } else {
        echo "<div style='background: #f8d7da; border: 1px solid #f5c6cb; color: #721c24; padding: 15px; border-radius: 5px; margin: 20px 0;'>";
        echo "<h3>❌ Setup had errors</h3>";
        echo "<p>Some operations failed. Please check the errors above and try again.</p>";
        echo "</div>";
    }
    
    // Show current database status
    echo "<h2>Database Status:</h2>";
    
    $tables = ['users', 'currencies', 'wallets', 'transactions'];
    echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
    echo "<tr><th>Table</th><th>Record Count</th></tr>";
    
    foreach ($tables as $table) {
        try {
            $stmt = $db->prepare("SELECT COUNT(*) as count FROM `{$table}`");
            $stmt->execute();
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            $count = $result['count'];
            echo "<tr><td>{$table}</td><td style='color: green;'>{$count}</td></tr>";
            $stmt->closeCursor();
        } catch (PDOException $e) {
            echo "<tr><td>{$table}</td><td style='color: red;'>Error: " . htmlspecialchars($e->getMessage()) . "</td></tr>";
        }
    }
    echo "</table>";
    
} catch (Exception $e) {
    echo "<div style='background: #f8d7da; border: 1px solid #f5c6cb; color: #721c24; padding: 15px; border-radius: 5px; margin: 20px 0;'>";
    echo "<h3>❌ Setup Failed</h3>";
    echo "<p>Error: " . htmlspecialchars($e->getMessage()) . "</p>";
    echo "</div>";
}
?>

<style>
body {
    font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
    max-width: 800px;
    margin: 0 auto;
    padding: 20px;
    line-height: 1.6;
}
h1, h2, h3 {
    color: #333;
}
ul {
    background: #f8f9fa;
    padding: 20px;
    border-radius: 5px;
    max-height: 400px;
    overflow-y: auto;
}
table {
    margin: 20px 0;
}
th, td {
    padding: 8px 12px;
    text-align: left;
}
th {
    background: #f8f9fa;
}
</style>