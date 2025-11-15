<?php
require_once 'db.php';
require_once 'draw_functions.php';

echo "Current time: " . (new DateTime())->format('Y-m-d H:i:s') . "\n";

// Check all draws in the table
$stmt = $pdo->query("SELECT * FROM draw_dates ORDER BY date ASC LIMIT 10");
$allDraws = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo "Total draws in table: " . count($allDraws) . "\n";
foreach ($allDraws as $draw) {
    echo "- " . $draw['date'] . "\n";
}

echo "\n--- Upcoming draws ---\n";
$upcoming = getUpcomingDraws($pdo, 10);
echo 'Found ' . count($upcoming) . ' upcoming draws' . "\n";
foreach ($upcoming as $draw) {
    echo '- ' . $draw['formatted'] . "\n";
}
?>
