<?php
// Database connection
$host = 'localhost';
$dbname = 'raffle';
$username = 'root';
$password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Check for phone 0548664851
    $stmt = $pdo->prepare("SELECT * FROM winners WHERE phone = ? OR phone = ? OR phone = ? OR phone = ?");
    $phone = '0548664851';
    $stmt->execute([$phone, '233' . substr($phone, 1), substr($phone, 1), '0' . substr($phone, 1)]);
    $winners = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "Searching for phone: $phone\n";
    echo "Found " . count($winners) . " winners\n\n";
    
    foreach ($winners as $winner) {
        echo "ID: " . $winner['id'] . "\n";
        echo "Name: " . $winner['name'] . "\n";
        echo "Phone: " . $winner['phone'] . "\n";
        echo "Is Claimed: " . ($winner['is_claimed'] ? 'Yes' : 'No') . "\n";
        echo "Draw Week: " . $winner['draw_week'] . "\n";
        echo "---\n";
    }
    
    // Check current draw
    $stmt = $pdo->prepare("
        SELECT *, 
        CASE 
            WHEN date > NOW() THEN 'upcoming'
            WHEN date <= NOW() AND date >= DATE_SUB(NOW(), INTERVAL 7 DAY) THEN 'active'
            ELSE 'expired'
        END as status
        FROM draw_dates 
        WHERE date >= DATE_SUB(NOW(), INTERVAL 7 DAY)
        ORDER BY date ASC 
        LIMIT 1
    ");
    $stmt->execute();
    $currentDraw = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($currentDraw) {
        echo "\nCurrent Draw:\n";
        echo "ID: " . $currentDraw['id'] . "\n";
        echo "Date: " . $currentDraw['date'] . "\n";
        echo "Status: " . $currentDraw['status'] . "\n";
    }
    
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>
