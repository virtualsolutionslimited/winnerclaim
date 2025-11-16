<?php
// Simple OTP test
require_once 'db.php';
require_once 'sms_functions.php';

$phone = "0548664851";

echo "<h2>OTP Test for phone: $phone</h2>";

// First, let's see what's in the winners table
$stmt = $pdo->prepare("SELECT * FROM winners WHERE phone LIKE ? ORDER BY id DESC LIMIT 5");
$stmt->execute(["%$phone%"]);
$winners = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo "<h3>Current records for this phone:</h3>";
foreach ($winners as $winner) {
    echo "<pre>";
    echo "ID: " . $winner['id'] . "\n";
    echo "Name: " . $winner['name'] . "\n";
    echo "Phone: " . $winner['phone'] . "\n";
    echo "Draw Week: " . $winner['draw_week'] . "\n";
    echo "OTP: " . ($winner['otp'] ?? 'NULL') . "\n";
    echo "Is Claimed: " . $winner['is_claimed'] . "\n";
    echo "</pre>";
}

// Send OTP
echo "<h3>Sending OTP...</h3>";
$result = sendOTP($phone);
echo "<pre>";
print_r($result);
echo "</pre>";

if ($result['status'] === 'success') {
    $otp = $result['code']; // Use 'code' instead of 'otp'
    echo "<h3>Generated OTP: <span style='color: red; font-size: 24px;'>$otp</span></h3>";
    
    // Check if it was stored
    $stmt = $pdo->prepare("SELECT otp FROM winners WHERE phone LIKE ? ORDER BY id DESC LIMIT 1");
    $stmt->execute(["%$phone"]);
    $stored = $stmt->fetch(PDO::FETCH_ASSOC);
    
    echo "<h3>Stored OTP in database: " . ($stored['otp'] ?? 'NULL') . "</h3>";
    
    // Test verification
    echo "<h3>Testing verification with OTP: $otp</h3>";
    $verifyResult = verifyOTPCode($phone, $otp);
    echo "<pre>";
    print_r($verifyResult);
    echo "</pre>";
    
    if ($verifyResult['status'] === 'success' && $verifyResult['verified']) {
        echo "<h3 style='color: green;'>✅ OTP Verification SUCCESSFUL!</h3>";
    } else {
        echo "<h3 style='color: red;'>❌ OTP Verification FAILED!</h3>";
    }
}
?>
