<?php
require_once 'db.php';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "<h2>Make Draw Week 20 Current</h2>";
    
    // First, unset all current draws
    $stmt = $pdo->prepare("UPDATE draw_dates SET is_current = 0");
    $stmt->execute();
    echo "<p>Unset all current draws</p>";
    
    // Make draw week 20 current
    $stmt = $pdo->prepare("UPDATE draw_dates SET is_current = 1 WHERE id = 20");
    $stmt->execute();
    echo "<p>Set draw week 20 as current</p>";
    
    // Verify
    $stmt = $pdo->prepare("SELECT * FROM draw_dates WHERE is_current = 1");
    $stmt->execute();
    $current = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($current) {
        echo "<h3>Current Draw is now:</h3>";
        echo "<p>ID: " . $current['id'] . "</p>";
        echo "<p>Week Name: " . htmlspecialchars($current['week_name']) . "</p>";
        echo "<p>Date: " . $current['date'] . "</p>";
    }
    
    echo "<p><a href='test_unclaimed.php'>Test Phone Lookup Now</a></p>";
    
} catch (Exception $e) {
    echo "<p style='color: red;'>Error: " . htmlspecialchars($e->getMessage()) . "</p>";
}
?>
