<?php
require_once 'includes/db.php';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "<h2>Draw Weeks in Database</h2>";
    
    // Get all draw weeks
    $stmt = $pdo->prepare("SELECT * FROM draw_dates ORDER BY id DESC");
    $stmt->execute();
    $draws = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
    echo "<tr><th>ID</th><th>Week Name</th><th>Date</th><th>Is Current</th></tr>";
    
    foreach ($draws as $draw) {
        echo "<tr>";
        echo "<td>" . $draw['id'] . "</td>";
        echo "<td>" . htmlspecialchars($draw['week_name']) . "</td>";
        echo "<td>" . $draw['date'] . "</td>";
        echo "<td>" . ($draw['is_current'] ? 'YES' : 'No') . "</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    // Check winners by draw week
    echo "<h2>Winners by Draw Week</h2>";
    $stmt = $pdo->prepare("
        SELECT draw_week, COUNT(*) as total, 
               SUM(CASE WHEN is_claimed = 0 THEN 1 ELSE 0 END) as unclaimed
        FROM winners 
        GROUP BY draw_week 
        ORDER BY draw_week DESC
    ");
    $stmt->execute();
    $stats = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
    echo "<tr><th>Draw Week</th><th>Total Winners</th><th>Unclaimed</th></tr>";
    
    foreach ($stats as $stat) {
        echo "<tr>";
        echo "<td>" . $stat['draw_week'] . "</td>";
        echo "<td>" . $stat['total'] . "</td>";
        echo "<td>" . $stat['unclaimed'] . "</td>";
        echo "</tr>";
    }
    echo "</table>";
    
} catch (Exception $e) {
    echo "<p style='color: red;'>Error: " . htmlspecialchars($e->getMessage()) . "</p>";
}
?>
