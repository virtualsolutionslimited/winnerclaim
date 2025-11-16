<?php
require_once 'includes/db.php';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "<h2>Database Debug Info</h2>";
    
    // Check current draw week
    $stmt = $pdo->prepare("SELECT * FROM draw_dates WHERE is_current = 1 ORDER BY id DESC LIMIT 1");
    $stmt->execute();
    $currentDraw = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($currentDraw) {
        echo "<h3>Current Draw Week: " . htmlspecialchars($currentDraw['week_name']) . "</h3>";
        echo "<p>Draw ID: " . $currentDraw['id'] . "</p>";
        echo "<p>Date: " . $currentDraw['date'] . "</p>";
        
        // Check unclaimed winners for current draw
        $stmt = $pdo->prepare("
            SELECT w.*, d.date as draw_date
            FROM winners w 
            LEFT JOIN draw_dates d ON w.draw_week = d.id 
            WHERE w.draw_week = ? AND w.is_claimed = 0
            ORDER BY w.createdAt ASC
        ");
        $stmt->execute([$currentDraw['id']]);
        $unclaimedWinners = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo "<h3>Unclaimed Winners (" . count($unclaimedWinners) . ")</h3>";
        
        if (count($unclaimedWinners) > 0) {
            echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
            echo "<tr><th>Name</th><th>Phone</th><th>Draw Week</th><th>Created</th></tr>";
            
            foreach ($unclaimedWinners as $winner) {
                echo "<tr>";
                echo "<td>" . htmlspecialchars($winner['name']) . "</td>";
                echo "<td>" . htmlspecialchars($winner['phone']) . "</td>";
                echo "<td>" . htmlspecialchars($winner['draw_week']) . "</td>";
                echo "<td>" . htmlspecialchars($winner['createdAt']) . "</td>";
                echo "</tr>";
            }
            echo "</table>";
        } else {
            echo "<p>No unclaimed winners found for current draw week.</p>";
        }
        
        // Check all winners (for testing)
        echo "<h3>All Winners (Last 10)</h3>";
        $stmt = $pdo->prepare("SELECT * FROM winners ORDER BY createdAt DESC LIMIT 10");
        $stmt->execute();
        $allWinners = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
        echo "<tr><th>Name</th><th>Phone</th><th>Draw Week</th><th>Claimed</th><th>Created</th></tr>";
        
        foreach ($allWinners as $winner) {
            echo "<tr>";
            echo "<td>" . htmlspecialchars($winner['name']) . "</td>";
            echo "<td>" . htmlspecialchars($winner['phone']) . "</td>";
            echo "<td>" . htmlspecialchars($winner['draw_week']) . "</td>";
            echo "<td>" . ($winner['is_claimed'] ? 'Yes' : 'No') . "</td>";
            echo "<td>" . htmlspecialchars($winner['createdAt']) . "</td>";
            echo "</tr>";
        }
        echo "</table>";
        
    } else {
        echo "<p>No current draw week found!</p>";
    }
    
} catch (Exception $e) {
    echo "<p style='color: red;'>Error: " . htmlspecialchars($e->getMessage()) . "</p>";
}
?>
