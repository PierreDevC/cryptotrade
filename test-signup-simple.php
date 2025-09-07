<?php
// Simple signup test
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>Simple Signup Test</h1>";

// Simulate the exact signup process
try {
    require_once "config.php";
    require_once "app/Core/Database.php";
    require_once "app/Core/Session.php";
    require_once "app/Core/Request.php";
    require_once "app/Utils/Csrf.php";
    require_once "app/Models/User.php";
    
    echo "<p>✓ All files loaded successfully</p>";
    
    // Test database connection
    $db = App\Core\Database::getInstance()->getConnection();
    echo "<p>✓ Database connected</p>";
    
    // Test if we can create a user
    $userModel = new App\Models\User($db);
    echo "<p>✓ User model created</p>";
    
    // Simulate form data
    $testData = [
        'fullname' => 'Test User ' . time(),
        'email' => 'test' . time() . '@example.com',
        'password' => 'testpassword123'
    ];
    
    echo "<p>Testing user creation with data:</p>";
    echo "<pre>" . print_r($testData, true) . "</pre>";
    
    // Try to create user
    $result = $userModel->create($testData);
    
    if ($result) {
        echo "<p style='color: green;'>✓ User created successfully!</p>";
        
        // Clean up - delete the test user
        $stmt = $db->prepare("DELETE FROM users WHERE email = ?");
        $stmt->execute([$testData['email']]);
        echo "<p>✓ Test user cleaned up</p>";
    } else {
        echo "<p style='color: red;'>❌ Failed to create user</p>";
        
        // Get PDO error info
        $errorInfo = $db->errorInfo();
        echo "<p>Database error: " . print_r($errorInfo, true) . "</p>";
    }
    
} catch (Exception $e) {
    echo "<div style='background: #f8d7da; padding: 15px; border-radius: 5px; margin: 20px 0;'>";
    echo "<h3>❌ Exception Occurred</h3>";
    echo "<p><strong>Message:</strong> " . htmlspecialchars($e->getMessage()) . "</p>";
    echo "<p><strong>File:</strong> " . $e->getFile() . "</p>";
    echo "<p><strong>Line:</strong> " . $e->getLine() . "</p>";
    echo "</div>";
} catch (Error $e) {
    echo "<div style='background: #f8d7da; padding: 15px; border-radius: 5px; margin: 20px 0;'>";
    echo "<h3>❌ Fatal Error Occurred</h3>";
    echo "<p><strong>Message:</strong> " . htmlspecialchars($e->getMessage()) . "</p>";
    echo "<p><strong>File:</strong> " . $e->getFile() . "</p>";
    echo "<p><strong>Line:</strong> " . $e->getLine() . "</p>";
    echo "</div>";
}

// Test if tables exist
try {
    echo "<h2>Database Tables Check</h2>";
    $tables = $db->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
    echo "<p>Available tables:</p><ul>";
    foreach ($tables as $table) {
        echo "<li>" . $table . "</li>";
    }
    echo "</ul>";
    
    if (in_array('users', $tables)) {
        $userCount = $db->query("SELECT COUNT(*) FROM users")->fetchColumn();
        echo "<p>Users table has {$userCount} records</p>";
    }
    
} catch (Exception $e) {
    echo "<p style='color: red;'>Error checking tables: " . $e->getMessage() . "</p>";
}
?>

<style>
body {
    font-family: Arial, sans-serif;
    max-width: 800px;
    margin: 0 auto;
    padding: 20px;
}
</style>
