<?php
/**
 * Test phone number normalization function
 */

function normalizePhoneForUpload($phone) {
    // Remove any non-digit characters
    $cleanPhone = preg_replace('/[^0-9]/', '', $phone);
    
    // If starts with 233, replace with 0
    if (strlen($cleanPhone) >= 12 && substr($cleanPhone, 0, 3) === '233') {
        return '0' . substr($cleanPhone, 3);
    }
    
    // If exactly 9 digits and doesn't start with 0, add 0 at the beginning
    if (strlen($cleanPhone) === 9 && substr($cleanPhone, 0, 1) !== '0') {
        return '0' . $cleanPhone;
    }
    
    // If starts with 0 and has correct length (10 digits), return as is
    if (strlen($cleanPhone) === 10 && substr($cleanPhone, 0, 1) === '0') {
        return $cleanPhone;
    }
    
    // Return original if no normalization needed
    return $phone;
}

// Test cases
$testPhones = [
    '548664851',        // 9 digits, should become 0548664851
    '233548664851',     // starts with 233, should become 0548664851
    '0201234567',       // already normalized, should stay same
    '0548664851',       // already normalized, should stay same
    '+233548664851',    // with +, should become 0548664851
    '233-548-664-851',  // with dashes, should become 0548664851
    '(233) 548-664-851', // with parentheses, should become 0548664851
    '0241234567',       // already normalized, should stay same
    '241234567',        // 9 digits, should become 0241234567
];

echo "Testing phone number normalization:\n\n";

foreach ($testPhones as $phone) {
    $normalized = normalizePhoneForUpload($phone);
    echo "Original: '$phone' -> Normalized: '$normalized'\n";
}

echo "\nTest completed.\n";
?>
