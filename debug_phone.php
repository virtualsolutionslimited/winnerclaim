<?php
require_once 'includes/db.php';
require_once 'winner_functions.php';

// Test the phone lookup
$phone = '0548664851';

echo "<h2>Debug Phone Lookup</h2>";
echo "<p>Testing phone: " . htmlspecialchars($phone) . "</p>";

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Get current draw week
    require_once 'draw_functions.php';
    $currentDraw = getCurrentDrawWeek($pdo);
    
    echo "<h3>Current Draw Week:</h3>";
    if ($currentDraw) {
        echo "<p>ID: " . $currentDraw['id'] . "</p>";
        echo "<p>Name: " . htmlspecialchars($currentDraw['week_name']) . "</p>";
        echo "<p>Date: " . $currentDraw['date'] . "</p>";
    } else {
        echo "<p>No current draw found</p>";
    }
    
    // Check if winner exists in database
    echo "<h3>Direct Database Query:</h3>";
    $stmt = $pdo->prepare("SELECT * FROM winners WHERE phone = ? AND is_claimed = 0");
    $stmt->execute([$phone]);
    $winners = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<p>Found " . count($winners) . " winners with phone " . htmlspecialchars($phone) . "</p>";
    
    foreach ($winners as $winner) {
        echo "<div style='background: #f0f0f0; padding: 10px; margin: 10px 0;'>";
        echo "<p><strong>ID:</strong> " . $winner['id'] . "</p>";
        echo "<p><strong>Name:</strong> " . htmlspecialchars($winner['name']) . "</p>";
        echo "<p><strong>Phone:</strong> " . htmlspecialchars($winner['phone']) . "</p>";
        echo "<p><strong>Draw Week:</strong> " . $winner['draw_week'] . "</p>";
        echo "<p><strong>Is Claimed:</strong> " . ($winner['is_claimed'] ? 'Yes' : 'No') . "</p>";
        echo "<p><strong>Created:</strong> " . $winner['createdAt'] . "</p>";
        echo "</div>";
    }
    
    // Test the function
    echo "<h3>Function Result:</h3>";
    $result = getUnclaimedWinnerByPhoneForCurrentDraw($pdo, $phone);
    echo "<pre>" . print_r($result, true) . "</pre>";
    
    // Test phone cleaning
    echo "<h3>Phone Cleaning:</h3>";
    $cleaned = cleanPhoneNumber($phone);
    echo "<p>Original: " . htmlspecialchars($phone) . "</p>";
    echo "<p>Cleaned: " . htmlspecialchars($cleaned) . "</p>";
    
    // Test with different formats
    $formats = ['0548664851', '548664851', '+233548664851', '233548664851'];
    echo "<h3>Testing Different Formats:</h3>";
    foreach ($formats as $format) {
        $result = getUnclaimedWinnerByPhoneForCurrentDraw($pdo, $format);
        echo "<p><strong>" . htmlspecialchars($format) . ":</strong> " . $result['status'] . "</p>";
    }
    
} catch (Exception $e) {
    echo "<p style='color: red;'>Error: " . htmlspecialchars($e->getMessage()) . "</p>";
}
?>
