<?php
require_once 'includes/db.php';
require_once 'winner_functions.php';

// Simulate API call
$phone = $_GET['phone'] ?? '548664851'; // Default test phone

echo "<h2>Testing Phone Verification</h2>";
echo "<p>Testing phone: " . htmlspecialchars($phone) . "</p>";

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Test the function directly
    $result = getUnclaimedWinnerByPhoneForCurrentDraw($pdo, $phone);
    
    echo "<h3>Function Result:</h3>";
    echo "<pre>" . print_r($result, true) . "</pre>";
    
    // Test phone cleaning
    echo "<h3>Phone Cleaning Test:</h3>";
    $testPhones = ['548664851', '0548664851', '+233548664851', '233548664851'];
    
    foreach ($testPhones as $testPhone) {
        $cleaned = cleanPhoneNumber($testPhone);
        echo "<p>$testPhone → $cleaned</p>";
    }
    
} catch (Exception $e) {
    echo "<p style='color: red;'>Error: " . htmlspecialchars($e->getMessage()) . "</p>";
}
?>
