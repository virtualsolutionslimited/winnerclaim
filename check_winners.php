<?php
require_once 'db.php';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "<h2>Winners Table Analysis</h2>";
    
    // Get all winners with their draw_week info
    $stmt = $pdo->prepare("
        SELECT w.*, d.week_name, d.date as draw_date, d.is_current
        FROM winners w 
        LEFT JOIN draw_dates d ON w.draw_week = d.id 
        ORDER BY w.id DESC
        LIMIT 10
    ");
    $stmt->execute();
    $winners = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
    echo "<tr><th>ID</th><th>Name</th><th>Phone</th><th>Draw Week</th><th>Draw Name</th><th>Draw Date</th><th>Is Current</th><th>Claimed</th></tr>";
    
    foreach ($winners as $winner) {
        echo "<tr>";
        echo "<td>" . $winner['id'] . "</td>";
        echo "<td>" . htmlspecialchars($winner['name']) . "</td>";
        echo "<td>" . htmlspecialchars($winner['phone']) . "</td>";
        echo "<td>" . $winner['draw_week'] . "</td>";
        echo "<td>" . htmlspecialchars($winner['week_name'] ?? 'N/A') . "</td>";
        echo "<td>" . ($winner['draw_date'] ?? 'N/A') . "</td>";
        echo "<td>" . ($winner['is_current'] ? 'YES' : 'No') . "</td>";
        echo "<td>" . ($winner['is_claimed'] ? 'Yes' : 'No') . "</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    // Check specifically for phone 0548664851
    echo "<h3>Specific Phone Search: 0548664851</h3>";
    $stmt = $pdo->prepare("SELECT * FROM winners WHERE phone = '0548664851'");
    $stmt->execute();
    $phoneWinners = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($phoneWinners as $winner) {
        echo "<div style='background: #e8f4fd; padding: 10px; margin: 10px 0; border-left: 4px solid #2196F3;'>";
        echo "<p><strong>ID:</strong> " . $winner['id'] . "</p>";
        echo "<p><strong>Name:</strong> " . htmlspecialchars($winner['name']) . "</p>";
        echo "<p><strong>Phone:</strong> " . htmlspecialchars($winner['phone']) . "</p>";
        echo "<p><strong>Draw Week:</strong> " . $winner['draw_week'] . "</p>";
        echo "<p><strong>Is Claimed:</strong> " . ($winner['is_claimed'] ? 'Yes' : 'No') . "</p>";
        echo "<p><strong>Created:</strong> " . $winner['createdAt'] . "</p>";
        echo "</div>";
    }
    
} catch (Exception $e) {
    echo "<p style='color: red;'>Error: " . htmlspecialchars($e->getMessage()) . "</p>";
}
?>
