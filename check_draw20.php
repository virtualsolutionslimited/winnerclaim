<?php
require_once 'db.php';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "<h2>Check Draw Week 20</h2>";
    
    // Check if draw week 20 exists
    $stmt = $pdo->prepare("SELECT * FROM draw_dates WHERE id = 20");
    $stmt->execute();
    $draw = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($draw) {
        echo "<h3>Draw Week 20 exists:</h3>";
        echo "<p>ID: " . $draw['id'] . "</p>";
        echo "<p>Week Name: " . htmlspecialchars($draw['week_name']) . "</p>";
        echo "<p>Date: " . $draw['date'] . "</p>";
        echo "<p>Is Current: " . ($draw['is_current'] ? 'YES' : 'No') . "</p>";
        
        // Check winners in this draw
        $stmt = $pdo->prepare("SELECT * FROM winners WHERE draw_week = 20 AND is_claimed = 0");
        $stmt->execute();
        $winners = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo "<h3>Unclaimed Winners in Draw 20:</h3>";
        echo "<p>Count: " . count($winners) . "</p>";
        
        foreach ($winners as $winner) {
            echo "<div style='background: #f0f0f0; padding: 10px; margin: 10px 0;'>";
            echo "<p><strong>Name:</strong> " . htmlspecialchars($winner['name']) . "</p>";
            echo "<p><strong>Phone:</strong> " . htmlspecialchars($winner['phone']) . "</p>";
            echo "<p><strong>ID:</strong> " . $winner['id'] . "</p>";
            echo "</div>";
        }
    } else {
        echo "<p>Draw week 20 does not exist in draw_dates table!</p>";
        
        // Show existing draw weeks
        $stmt = $pdo->prepare("SELECT * FROM draw_dates ORDER BY id DESC LIMIT 5");
        $stmt->execute();
        $draws = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo "<h3>Existing Draw Weeks:</h3>";
        foreach ($draws as $draw) {
            echo "<p>ID " . $draw['id'] . ": " . htmlspecialchars($draw['week_name']) . "</p>";
        }
    }
    
} catch (Exception $e) {
    echo "<p style='color: red;'>Error: " . htmlspecialchars($e->getMessage()) . "</p>";
}
?>
