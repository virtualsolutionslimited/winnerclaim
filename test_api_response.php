<?php
require_once 'sms_functions.php';

// Test the API directly
$testPhone = '548664851';
$message = "Test message";

echo "<h2>Testing SMS API Response</h2>";

$apiKey = 'akR3cGxLb3JwRXpaemFrUFRXR0Y';
$senderId = 'Raffle';

// Clean phone number
$cleanPhone = cleanPhoneNumber($testPhone);

// API endpoint
$url = 'https://sms.arkesel.com/sms/api';

// Parameters
$params = [
    'action' => 'send-sms',
    'api_key' => $apiKey,
    'to' => $cleanPhone,
    'from' => $senderId,
    'sms' => $message
];

// Build query string
$queryString = http_build_query($params);
$fullUrl = $url . '?' . $queryString;

echo "<h3>Request URL:</h3>";
echo "<code>" . htmlspecialchars($fullUrl) . "</code>";

echo "<h3>Raw Response:</h3>";

try {
    // Send request
    $response = file_get_contents($fullUrl);
    
    echo "<pre>" . htmlspecialchars($response) . "</pre>";
    
    // Parse response
    $result = json_decode($response, true);
    
    echo "<h3>Parsed JSON:</h3>";
    echo "<pre>" . print_r($result, true) . "</pre>";
    
    echo "<h3>Analysis:</h3>";
    if ($result) {
        echo "<ul>";
        foreach ($result as $key => $value) {
            echo "<li><strong>$key:</strong> " . htmlspecialchars(print_r($value, true)) . "</li>";
        }
        echo "</ul>";
        
        // Check success conditions
        echo "<h3>Success Check:</h3>";
        echo "<ul>";
        if (isset($result['status'])) {
            echo "<li>Status: " . $result['status'] . " (matches 'ok'? " . ($result['status'] === 'ok' ? 'YES' : 'NO') . ")</li>";
            echo "<li>Status (lowercase): " . strtolower($result['status']) . " (matches 'success'? " . (strtolower($result['status']) === 'success' ? 'YES' : 'NO') . ")</li>";
        }
        if (isset($result['message'])) {
            echo "<li>Message: " . $result['message'] . "</li>";
            echo "<li>Message contains 'success'? " . (strpos(strtolower($result['message']), 'success') !== false ? 'YES' : 'NO') . "</li>";
            echo "<li>Message contains 'sent'? " . (strpos(strtolower($result['message']), 'sent') !== false ? 'YES' : 'NO') . "</li>";
        }
        echo "</ul>";
    } else {
        echo "<p>Failed to parse JSON response</p>";
    }
    
} catch (Exception $e) {
    echo "<p>Error: " . htmlspecialchars($e->getMessage()) . "</p>";
}
?>
