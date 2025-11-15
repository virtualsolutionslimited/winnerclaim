<?php
require_once 'db.php';
require_once 'draw_functions.php';

echo "<h3>Debug Claim Window Calculation</h3>";

// Get current draw week
$currentDraw = getCurrentDrawWeek($pdo);

if ($currentDraw) {
    echo "<p><strong>Current Draw Found:</strong></p>";
    echo "<pre>" . print_r($currentDraw, true) . "</pre>";
    
    // Calculate claim window: 5 days from draw date
    $drawDate = new DateTime($currentDraw['date']);
    $claimWindowDate = clone $drawDate;
    $claimWindowDate->add(new DateInterval('P5D')); // Add 5 days
    
    $now = new DateTime();
    
    echo "<p><strong>Draw Date:</strong> " . $drawDate->format('Y-m-d H:i:s') . "</p>";
    echo "<p><strong>Claim Window (5 days after draw):</strong> " . $claimWindowDate->format('Y-m-d H:i:s') . "</p>";
    echo "<p><strong>Current Time:</strong> " . $now->format('Y-m-d H:i:s') . "</p>";
    
    // Check if expired
    $isExpired = $claimWindowDate < $now;
    echo "<p><strong>Is Expired:</strong> " . ($isExpired ? 'YES' : 'NO') . "</p>";
    
    if ($isExpired) {
        $timeAgo = $now->diff($claimWindowDate);
        echo "<p><strong>Expired Since:</strong> " . $timeAgo->days . " days ago</p>";
    }
    
    // What will be passed to JavaScript
    echo "<p><strong>JavaScript Date String:</strong> " . $claimWindowDate->format('Y-m-d H:i:s') . "</p>";
    
} else {
    echo "<p>No current draw found</p>";
}

// Test JavaScript date creation
echo "<h3>JavaScript Date Test</h3>";
echo "<script>";
echo "console.log('Current time:', new Date());";
echo "console.log('Claim window date:', new Date('" . ($claimWindowDate ? $claimWindowDate->format('Y-m-d H:i:s') : 'null') . "'));";
echo "console.log('Is expired:', new Date('" . ($claimWindowDate ? $claimWindowDate->format('Y-m-d H:i:s') : 'null') . "') < new Date());";
echo "</script>";
?>
