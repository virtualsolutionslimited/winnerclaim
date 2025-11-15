<?php
require_once 'db.php';
require_once 'winner_functions.php';

// Get unclaimed winners for current draw
$result = getUnclaimedWinnersForCurrentDraw($pdo);

// Create text output
$textOutput = "========================================\n";
$textOutput .= "UNCLAIMED WINNINGS REPORT\n";
$textOutput .= "Generated: " . date('Y-m-d H:i:s') . "\n";
$textOutput .= "========================================\n\n";

if ($result['status'] === 'success') {
    $textOutput .= "STATUS: SUCCESS\n";
    $textOutput .= "MESSAGE: " . $result['message'] . "\n\n";
    
    // Current draw info
    if (isset($result['current_draw'])) {
        $draw = $result['current_draw'];
        $textOutput .= "CURRENT DRAW WEEK:\n";
        $textOutput .= "  ID: " . $draw['id'] . "\n";
        $textOutput .= "  Date: " . $draw['formatted'] . "\n";
        $textOutput .= "  Status: " . $draw['status'] . "\n\n";
    }
    
    // Summary
    $textOutput .= "SUMMARY:\n";
    $textOutput .= "  Total Unclaimed: " . $result['total_unclaimed'] . "\n";
    
    // Calculate additional stats
    $urgentCount = 0;
    $recentCount = 0;
    foreach ($result['unclaimed_winners'] as $winner) {
        if ($winner['days_since_win'] >= 5) {
            $urgentCount++;
        }
        if ($winner['days_since_win'] <= 2) {
            $recentCount++;
        }
    }
    $textOutput .= "  Urgent (5+ days): " . $urgentCount . "\n";
    $textOutput .= "  Recent (≤2 days): " . $recentCount . "\n\n";
    
    // Unclaimed winners details
    if (!empty($result['unclaimed_winners'])) {
        $textOutput .= "UNCLAIMED WINNERS DETAILS:\n";
        $textOutput .= "----------------------------------------\n";
        
        foreach ($result['unclaimed_winners'] as $index => $winner) {
            $textOutput .= "\n[" . ($index + 1) . "] " . strtoupper($winner['name']) . "\n";
            $textOutput .= "    Phone: " . $winner['phone'] . "\n";
            $textOutput .= "    Draw Date: " . date('M j, Y g:i A', strtotime($winner['draw_date'])) . "\n";
            $textOutput .= "    Won On: " . date('M j, Y g:i A', strtotime($winner['created_at'])) . "\n";
            $textOutput .= "    Days Since Win: " . $winner['days_since_win'] . " ";
            
            if ($winner['days_since_win'] >= 5) {
                $textOutput .= "(URGENT!)\n";
            } elseif ($winner['days_since_win'] <= 2) {
                $textOutput .= "(Recent)\n";
            } else {
                $textOutput .= "\n";
            }
            
            $textOutput .= "    Status: UNCLAIMED\n";
            $textOutput .= "    Winner ID: " . $winner['id'] . "\n";
        }
    } else {
        $textOutput .= "No unclaimed winners found in current draw week.\n";
    }
    
} else {
    $textOutput .= "STATUS: ERROR\n";
    $textOutput .= "MESSAGE: " . $result['message'] . "\n";
}

$textOutput .= "\n========================================\n";
$textOutput .= "END OF REPORT\n";
$textOutput .= "========================================\n";

// Save to file
$filename = 'unclaimed_winners_report_' . date('Y-m-d_H-i-s') . '.txt';
file_put_contents($filename, $textOutput);

echo "Report generated: $filename\n";
echo "Content preview:\n\n";
echo $textOutput;

?>
