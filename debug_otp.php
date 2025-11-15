<?php
require_once 'db.php';
require_once 'draw_functions.php';
require_once 'sms_functions.php';

echo "<h2>OTP Debug Information</h2>";

// Get current draw week
$currentDraw = getCurrentDrawWeek($pdo);
echo "<h3>Current Draw Week:</h3>";
echo "<pre>" . json_encode($currentDraw, JSON_PRETTY_PRINT) . "</pre>";

// Check winners table structure
echo "<h3>Winners Table Structure:</h3>";
$stmt = $pdo->query("DESCRIBE winners");
$columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
echo "<table border='1'>";
echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th></tr>";
foreach ($columns as $col) {
    echo "<tr><td>{$col['Field']}</td><td>{$col['Type']}</td><td>{$col['Null']}</td><td>{$col['Key']}</td></tr>";
}
echo "</table>";

// Check winners for current week
echo "<h3>Winners for Current Week ({$currentDraw['id']}):</h3>";
$stmt = $pdo->prepare("SELECT id, name, phone, draw_week, otp FROM winners WHERE draw_week = ? LIMIT 10");
$stmt->execute([$currentDraw['id']]);
$winners = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (empty($winners)) {
    echo "<p>No winners found for current week. Checking all weeks...</p>";
    $stmt = $pdo->query("SELECT id, name, phone, draw_week, otp FROM winners LIMIT 10");
    $winners = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

echo "<table border='1'>";
echo "<tr><th>ID</th><th>Name</th><th>Phone</th><th>Draw Week</th><th>OTP</th><th>Cleaned Phone</th></tr>";
foreach ($winners as $winner) {
    $cleaned = cleanPhoneNumber($winner['phone']);
    echo "<tr>";
    echo "<td>{$winner['id']}</td>";
    echo "<td>{$winner['name']}</td>";
    echo "<td>{$winner['phone']}</td>";
    echo "<td>{$winner['draw_week']}</td>";
    echo "<td>{$winner['otp']}</td>";
    echo "<td>$cleaned</td>";
    echo "</tr>";
}
echo "</table>";

// Test phone number cleaning
echo "<h3>Phone Number Cleaning Test:</h3>";
$testPhones = ['0201234567', '+233201234567', '233201234567', '020-123-4567', '(020) 123-4567'];
echo "<table border='1'>";
echo "<tr><th>Original</th><th>Cleaned</th></tr>";
foreach ($testPhones as $phone) {
    $cleaned = cleanPhoneNumber($phone);
    echo "<tr><td>$phone</td><td>$cleaned</td></tr>";
}
echo "</table>";

// Test the actual query if we have sample data
if (!empty($winners)) {
    echo "<h3>Testing Query with Sample Data:</h3>";
    $testPhone = $winners[0]['phone'];
    $cleanPhone = cleanPhoneNumber($testPhone);
    
    echo "<p>Testing with phone: $testPhone (cleaned: $cleanPhone)</p>";
    
    // Test the same query used in updateWinnerOTP
    $stmt = $pdo->prepare("
        SELECT * FROM winners 
        WHERE draw_week = ? AND (
            REPLACE(REPLACE(REPLACE(phone, ' ', ''), '-', ''), '(', '') = ? OR
            REPLACE(REPLACE(REPLACE(phone, ' ', ''), '-', ''), '(', '') LIKE ? OR
            phone LIKE ? OR
            phone = ?
        )
    ");
    
    $searchPatterns = [
        $cleanPhone,
        $cleanPhone . '%',
        '%' . $testPhone . '%',
        $testPhone
    ];
    
    $stmt->execute([$currentDraw['id'], ...$searchPatterns]);
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<p>Query found " . count($results) . " matches</p>";
    echo "<pre>" . json_encode($results, JSON_PRETTY_PRINT) . "</pre>";
    
    // Show the actual SQL with parameters
    echo "<h4>SQL Query:</h4>";
    echo "<code>SELECT * FROM winners WHERE draw_week = {$currentDraw['id']} AND (REPLACE(REPLACE(REPLACE(phone, ' ', ''), '-', ''), '(', '') = '$cleanPhone' OR REPLACE(REPLACE(REPLACE(phone, ' ', ''), '-', ''), '(', '') LIKE '$cleanPhone%' OR phone LIKE '%$testPhone%' OR phone = '$testPhone')</code>";
}
?>
