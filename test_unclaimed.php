<?php
require_once 'db.php';
require_once 'winner_functions.php';

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Unclaimed Winnings Test - Raffle System</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px;
        }
        
        .container {
            max-width: 1200px;
            margin: 0 auto;
        }
        
        .card {
            background: white;
            border-radius: 15px;
            padding: 30px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
            margin-bottom: 30px;
        }
        
        h1 {
            color: #333;
            margin-bottom: 10px;
            font-size: 2em;
        }
        
        .subtitle {
            color: #666;
            margin-bottom: 30px;
            font-size: 1.1em;
        }
        
        .result {
            padding: 20px;
            border-radius: 10px;
            margin: 20px 0;
        }
        
        .result.success {
            background: #d4edda;
            border: 1px solid #c3e6cb;
            color: #155724;
        }
        
        .result.error {
            background: #f8d7da;
            border: 1px solid #f5c6cb;
            color: #721c24;
        }
        
        .result.info {
            background: #d1ecf1;
            border: 1px solid #bee5eb;
            color: #0c5460;
        }
        
        .draw-info {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 8px;
            margin: 15px 0;
            border-left: 4px solid #007bff;
        }
        
        .winner-card {
            background: #fff;
            border: 1px solid #dee2e6;
            border-radius: 8px;
            padding: 20px;
            margin: 15px 0;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }
        
        .winner-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.15);
        }
        
        .winner-name {
            font-size: 1.2em;
            font-weight: bold;
            color: #333;
            margin-bottom: 10px;
        }
        
        .winner-details {
            color: #666;
            line-height: 1.6;
        }
        
        .status-badge {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 0.85em;
            font-weight: bold;
            margin-top: 10px;
        }
        
        .status-unclaimed {
            background: linear-gradient(135deg, #ff6b6b, #ee5a24);
            color: white;
        }
        
        .days-warning {
            color: #ff6b6b;
            font-weight: bold;
        }
        
        .days-normal {
            color: #28a745;
        }
        
        .summary-stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin: 20px 0;
        }
        
        .stat-card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 20px;
            border-radius: 10px;
            text-align: center;
        }
        
        .stat-number {
            font-size: 2.5em;
            font-weight: bold;
            margin-bottom: 5px;
        }
        
        .stat-label {
            font-size: 0.9em;
            opacity: 0.9;
        }
        
        .no-winners {
            text-align: center;
            padding: 40px;
            color: #666;
            font-style: italic;
        }
        
        .refresh-btn {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            padding: 12px 24px;
            border-radius: 8px;
            cursor: pointer;
            font-size: 1em;
            margin: 10px 0;
            transition: transform 0.2s ease;
        }
        
        .refresh-btn:hover {
            transform: translateY(-2px);
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="card">
            <h1>🎯 Unclaimed Winnings Test</h1>
            <p class="subtitle">Check for winners in the current draw week who haven't claimed their prizes yet</p>
            
            <button class="refresh-btn" onclick="location.reload()">🔄 Refresh Results</button>
            
            <?php
            // Test the function
            $result = getUnclaimedWinnersForCurrentDraw($pdo);
            
            if ($result['status'] === 'success') {
                echo '<div class="result success">';
                echo '<strong>✅ Function executed successfully!</strong><br>';
                echo htmlspecialchars($result['message']);
                echo '</div>';
                
                // Display current draw info
                if (isset($result['current_draw'])) {
                    $draw = $result['current_draw'];
                    echo '<div class="draw-info">';
                    echo '<strong>📅 Current Draw Week:</strong><br>';
                    echo 'Date: ' . htmlspecialchars($draw['formatted']) . '<br>';
                    echo 'Status: ' . ucfirst($draw['status']) . '<br>';
                    echo 'Draw Week ID: ' . htmlspecialchars($draw['id']);
                    echo '</div>';
                }
                
                // Display summary statistics
                echo '<div class="summary-stats">';
                echo '<div class="stat-card">';
                echo '<div class="stat-number">' . $result['total_unclaimed'] . '</div>';
                echo '<div class="stat-label">Unclaimed Winners</div>';
                echo '</div>';
                
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
                
                echo '<div class="stat-card" style="background: linear-gradient(135deg, #ff6b6b, #ee5a24);">';
                echo '<div class="stat-number">' . $urgentCount . '</div>';
                echo '<div class="stat-label">Urgent (5+ days)</div>';
                echo '</div>';
                
                echo '<div class="stat-card" style="background: linear-gradient(135deg, #28a745, #20bf6b);">';
                echo '<div class="stat-number">' . $recentCount . '</div>';
                echo '<div class="stat-label">Recent (≤2 days)</div>';
                echo '</div>';
                echo '</div>';
                
                // Display unclaimed winners
                if (!empty($result['unclaimed_winners'])) {
                    echo '<h3 style="margin: 30px 0 20px 0; color: #333;">🏆 Unclaimed Winners</h3>';
                    
                    foreach ($result['unclaimed_winners'] as $winner) {
                        echo '<div class="winner-card">';
                        echo '<div class="winner-name">' . htmlspecialchars($winner['name']) . '</div>';
                        echo '<div class="winner-details">';
                        echo '📱 Phone: ' . htmlspecialchars($winner['phone']) . '<br>';
                        echo '📅 Draw Date: ' . date('F j, Y g:i A', strtotime($winner['draw_date'])) . '<br>';
                        echo '🕐 Won: ' . date('F j, Y g:i A', strtotime($winner['created_at'])) . '<br>';
                        
                        // Days since winning with color coding
                        $daysClass = $winner['days_since_win'] >= 5 ? 'days-warning' : 'days-normal';
                        echo '<span class="' . $daysClass . '">⏰ Days since win: ' . $winner['days_since_win'] . '</span>';
                        
                        echo '<br><span class="status-badge status-unclaimed">❌ Unclaimed</span>';
                        echo '</div>';
                        echo '</div>';
                    }
                } else {
                    echo '<div class="no-winners">';
                    echo '🎉 No unclaimed winners found in the current draw week!<br>';
                    echo '<small>All winners have claimed their prizes or no winners have been selected yet.</small>';
                    echo '</div>';
                }
                
            } else {
                echo '<div class="result error">';
                echo '<strong>❌ Error:</strong><br>';
                echo htmlspecialchars($result['message']);
                echo '</div>';
            }
            ?>
            
            <div style="margin-top: 40px; padding: 20px; background: #f8f9fa; border-radius: 8px;">
                <h4 style="margin-bottom: 15px; color: #333;">📋 Function Details</h4>
                <ul style="color: #666; line-height: 1.8;">
                    <li><strong>Function:</strong> <code>getUnclaimedWinnersForCurrentDraw($pdo)</code></li>
                    <li><strong>Purpose:</strong> Retrieves all winners from the current draw week who haven't claimed their prizes yet</li>
                    <li><strong>Returns:</strong> Array with unclaimed winners, current draw info, and statistics</li>
                    <li><strong>Filter:</strong> Only shows winners where <code>is_claimed = 0</code> for the current draw week</li>
                    <li><strong>Order:</strong> Sorted by when they won (oldest first)</li>
                    <li><strong>Additional Data:</strong> Includes days since winning for urgency tracking</li>
                </ul>
            </div>
            
            <div style="margin-top: 20px; padding: 15px; background: #e3f2fd; border-radius: 8px;">
                <h4 style="margin-bottom: 10px; color: #1976d2;">🔍 Test Results</h4>
                <p style="color: #666; line-height: 1.6;">
                    This test page shows the real-time data from your database. Use it to verify that the function 
                    correctly identifies unclaimed winners for the current draw week. The statistics help you track 
                    which winners need urgent follow-up based on how long ago they won.
                </p>
            </div>
        </div>
    </div>
</body>
</html>
