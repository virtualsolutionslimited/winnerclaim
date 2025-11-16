<?php
require_once 'db.php';
require_once 'draw_functions.php';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "<h2>Current Draw Week Analysis</h2>";
    
    // Get current draw week using the function
    $currentDraw = getCurrentDrawWeek($pdo);
    
    if ($currentDraw) {
        echo "<h3>Current Draw (from function):</h3>";
        echo "<p>ID: " . $currentDraw['id'] . "</p>";
        echo "<p>Week Name: " . htmlspecialchars($currentDraw['week_name']) . "</p>";
        echo "<p>Date: " . $currentDraw['date'] . "</p>";
        
        // Check unclaimed winners in this draw
        $stmt = $pdo->prepare("
            SELECT w.*, d.date as draw_date
            FROM winners w 
            LEFT JOIN draw_dates d ON w.draw_week = d.id 
            WHERE w.draw_week = ? AND w.is_claimed = 0
        ");
        $stmt->execute([$currentDraw['id']]);
        $unclaimed = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo "<h3>Unclaimed Winners in Current Draw (" . count($unclaimed) . "):</h3>";
        foreach ($unclaimed as $winner) {
            echo "<div style='background: #e8f5e8; padding: 10px; margin: 10px 0;'>";
            echo "<p><strong>Name:</strong> " . htmlspecialchars($winner['name']) . "</p>";
            echo "<p><strong>Phone:</strong> " . htmlspecialchars($winner['phone']) . "</p>";
            echo "<p><strong>Draw Week:</strong> " . $winner['draw_week'] . "</p>";
            echo "</div>";
        }
    } else {
        echo "<p>No current draw week found!</p>";
    }
    
    // Also check what's marked as current in database
    echo "<h3>Database Current Flag Check:</h3>";
    $stmt = $pdo->prepare("SELECT * FROM draw_dates WHERE is_current = 1");
    $stmt->execute();
    $markedCurrent = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (count($markedCurrent) > 0) {
        foreach ($markedCurrent as $draw) {
            echo "<div style='background: #fff3cd; padding: 10px; margin: 10px 0;'>";
            echo "<p><strong>ID:</strong> " . $draw['id'] . "</p>";
            echo "<p><strong>Week Name:</strong> " . htmlspecialchars($draw['week_name']) . "</p>";
            echo "<p><strong>Date:</strong> " . $draw['date'] . "</p>";
            echo "</div>";
        }
    } else {
        echo "<p>No draw weeks marked as current in database!</p>";
    }
    
} catch (Exception $e) {
    echo "<p style='color: red;'>Error: " . htmlspecialchars($e->getMessage()) . "</p>";
}
?>
