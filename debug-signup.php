<?php
// Debug script for signup issues
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>CryptoTrade Signup Debug</h1>";

try {
    // Test basic includes
    echo "<h2>1. Testing Basic Includes</h2>";
    require_once "config.php";
    echo "✓ config.php loaded<br>";
    
    require_once "app/Core/Database.php";
    echo "✓ Database.php loaded<br>";
    
    require_once "app/Core/Session.php";
    echo "✓ Session.php loaded<br>";
    
    // Test database connection
    echo "<h2>2. Testing Database Connection</h2>";
    $db = App\Core\Database::getInstance()->getConnection();
    echo "✓ Database connection successful<br>";
    
    // Test if users table exists
    echo "<h2>3. Testing Database Tables</h2>";
    $stmt = $db->query("SHOW TABLES LIKE 'users'");
    if ($stmt->rowCount() > 0) {
        echo "✓ Users table exists<br>";
        
        // Check table structure
        $stmt = $db->query("DESCRIBE users");
        $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo "Users table columns:<br>";
        foreach ($columns as $col) {
            echo "- " . $col['Field'] . " (" . $col['Type'] . ")<br>";
        }
    } else {
        echo "❌ Users table does not exist<br>";
        echo "<p style='color: red;'>You need to run the database setup first!</p>";
        echo "<a href='database-setup.php'>Run Database Setup</a>";
    }
    
    // Test User model
    echo "<h2>4. Testing User Model</h2>";
    require_once "app/Models/User.php";
    $userModel = new App\Models\User($db);
    echo "✓ User model instantiated<br>";
    
    // Test CSRF
    echo "<h2>5. Testing CSRF</h2>";
    require_once "app/Utils/Csrf.php";
    $token = App\Utils\Csrf::generateToken();
    echo "✓ CSRF token generated: " . substr($token, 0, 10) . "...<br>";
    
    // Test creating a user (simulation)
    echo "<h2>6. Testing User Creation (Simulation)</h2>";
    $testEmail = "test_" . time() . "@example.com";
    $userData = [
        'fullname' => 'Test User',
        'email' => $testEmail,
        'password' => 'testpassword123'
    ];
    
    // Check if email already exists
    $existingUser = $userModel->findByEmail($testEmail);
    if (!$existingUser) {
        $result = $userModel->create($userData);
        if ($result) {
            echo "✓ Test user created successfully<br>";
            // Clean up - delete test user
            $stmt = $db->prepare("DELETE FROM users WHERE email = ?");
            $stmt->execute([$testEmail]);
            echo "✓ Test user cleaned up<br>";
        } else {
            echo "❌ Failed to create test user<br>";
        }
    } else {
        echo "❌ Test email already exists<br>";
    }
    
    echo "<h2>7. Environment Check</h2>";
    echo "PHP Version: " . PHP_VERSION . "<br>";
    echo "Railway Domain: " . ($_ENV['RAILWAY_PUBLIC_DOMAIN'] ?? 'Not set') . "<br>";
    echo "Base URL: " . BASE_URL . "<br>";
    echo "MySQL URL: " . (isset($_ENV['MYSQL_URL']) ? 'Set' : 'Not set') . "<br>";
    
} catch (Exception $e) {
    echo "<div style='background: #f8d7da; border: 1px solid #f5c6cb; color: #721c24; padding: 15px; border-radius: 5px; margin: 20px 0;'>";
    echo "<h3>❌ Error Occurred</h3>";
    echo "<p><strong>Message:</strong> " . htmlspecialchars($e->getMessage()) . "</p>";
    echo "<p><strong>File:</strong> " . $e->getFile() . "</p>";
    echo "<p><strong>Line:</strong> " . $e->getLine() . "</p>";
    echo "<p><strong>Stack Trace:</strong></p>";
    echo "<pre>" . htmlspecialchars($e->getTraceAsString()) . "</pre>";
    echo "</div>";
}

echo "<h2>8. Test Signup Form</h2>";
echo '<form method="POST" action="/signup" style="border: 1px solid #ccc; padding: 20px; max-width: 400px;">';
echo '<h3>Test Signup</h3>';
if (class_exists('App\Utils\Csrf')) {
    echo '<input type="hidden" name="' . App\Utils\Csrf::getTokenName() . '" value="' . App\Utils\Csrf::generateToken() . '">';
}
echo '<div style="margin-bottom: 10px;">';
echo '<label>Full Name:</label><br>';
echo '<input type="text" name="fullName" value="Test User" required style="width: 100%; padding: 5px;">';
echo '</div>';
echo '<div style="margin-bottom: 10px;">';
echo '<label>Email:</label><br>';
echo '<input type="email" name="email" value="test@example.com" required style="width: 100%; padding: 5px;">';
echo '</div>';
echo '<div style="margin-bottom: 10px;">';
echo '<label>Password:</label><br>';
echo '<input type="password" name="password" value="password123" required style="width: 100%; padding: 5px;">';
echo '</div>';
echo '<div style="margin-bottom: 10px;">';
echo '<label>Confirm Password:</label><br>';
echo '<input type="password" name="confirmPassword" value="password123" required style="width: 100%; padding: 5px;">';
echo '</div>';
echo '<button type="submit" style="background: #007bff; color: white; padding: 10px 20px; border: none; border-radius: 5px;">Test Signup</button>';
echo '</form>';
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
</style>
