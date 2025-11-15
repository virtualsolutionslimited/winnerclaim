<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Winner Phone Check - Test</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px;
        }
        
        .container {
            max-width: 800px;
            margin: 0 auto;
        }
        
        .card {
            background: white;
            border-radius: 15px;
            padding: 30px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            margin-bottom: 25px;
        }
        
        h1 {
            color: #333;
            margin-bottom: 20px;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #333;
        }
        
        input[type="text"] {
            width: 100%;
            padding: 12px 15px;
            border: 2px solid #e1e5e9;
            border-radius: 8px;
            font-size: 1rem;
            transition: all 0.3s ease;
        }
        
        input[type="text"]:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }
        
        .btn {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            border: none;
            padding: 12px 25px;
            border-radius: 8px;
            cursor: pointer;
            font-size: 1rem;
            font-weight: 600;
            transition: transform 0.2s ease;
        }
        
        .btn:hover {
            transform: scale(1.05);
        }
        
        .result {
            margin-top: 20px;
            padding: 15px;
            border-radius: 8px;
            border-left: 4px solid;
        }
        
        .result.success {
            background: #d4edda;
            border-color: #28a745;
            color: #155724;
        }
        
        .result.error {
            background: #f8d7da;
            border-color: #dc3545;
            color: #721c24;
        }
        
        .result.info {
            background: #d1ecf1;
            border-color: #17a2b8;
            color: #0c5460;
        }
        
        .winner-info {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 8px;
            margin-top: 10px;
        }
        
        .winner-info strong {
            color: #333;
        }
        
        .test-section {
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #e9ecef;
        }
        
        .current-winners {
            margin-top: 20px;
        }
        
        .winner-list {
            list-style: none;
            margin-top: 10px;
        }
        
        .winner-list li {
            background: #f8f9fa;
            padding: 10px;
            margin-bottom: 5px;
            border-radius: 5px;
            border-left: 3px solid #667eea;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="card">
            <h1>🏆 Winner Phone Check Test</h1>
            
            <form method="post">
                <div class="form-group">
                    <label for="phone">Enter Phone Number to Check:</label>
                    <input type="text" name="phone" id="phone" 
                           placeholder="e.g., 0201234567 or 020-123-4567" 
                           value="<?php echo isset($_POST['phone']) ? htmlspecialchars($_POST['phone']) : ''; ?>"
                           required>
                </div>
                
                <button type="submit" class="btn">🔍 Check Phone Number</button>
            </form>
            
            <?php
            if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['phone'])) {
                require_once 'winner_functions.php';
                
                $phone = trim($_POST['phone']);
                $result = checkWinnerForCurrentWeek($pdo, $phone);
                
                echo '<div class="result ' . $result['status'] . '">';
                echo '<strong>' . $result['message'] . '</strong>';
                
                if ($result['found'] && isset($result['winner'])) {
                    echo '<div class="winner-info">';
                    echo '<strong>Winner Details:</strong><br>';
                    echo 'Name: ' . htmlspecialchars($result['winner']['name']) . '<br>';
                    echo 'Phone: ' . htmlspecialchars($result['winner']['phone']) . '<br>';
                    echo 'Draw Date: ' . $result['winner']['draw_date'] . '<br>';
                    echo 'Status: ' . ($result['winner']['is_claimed'] ? 'Claimed' : 'Not Claimed') . '<br>';
                    echo 'Added: ' . $result['winner']['created_at'];
                    echo '</div>';
                }
                
                if (isset($result['current_draw'])) {
                    echo '<br><small>Current Draw Week: ' . $result['current_draw']['formatted'] . '</small>';
                }
                
                echo '</div>';
            }
            ?>
        </div>
        
        <div class="card test-section">
            <h2>📋 Current Week Winners</h2>
            
            <?php
            require_once 'winner_functions.php';
            $currentWinners = getCurrentWeekWinners($pdo);
            
            if (!empty($currentWinners)) {
                echo '<p><strong>Total winners this week: ' . count($currentWinners) . '</strong></p>';
                echo '<ul class="winner-list">';
                foreach ($currentWinners as $winner) {
                    echo '<li>';
                    echo '<strong>' . htmlspecialchars($winner['name']) . '</strong> - ';
                    echo htmlspecialchars($winner['phone']) . ' ';
                    echo '(' . ($winner['is_claimed'] ? 'Claimed' : 'Not Claimed') . ')';
                    echo '</li>';
                }
                echo '</ul>';
            } else {
                echo '<p style="color: #666;">No winners found for the current draw week.</p>';
            }
            ?>
        </div>
        
        <div class="card test-section">
            <h2>🔍 Advanced Search (All Weeks)</h2>
            
            <form method="post">
                <input type="hidden" name="search_all" value="1">
                <div class="form-group">
                    <label for="search_phone">Search Across All Weeks:</label>
                    <input type="text" name="search_phone" id="search_phone" 
                           placeholder="Enter phone number to search all weeks"
                           value="<?php echo isset($_POST['search_phone']) ? htmlspecialchars($_POST['search_phone']) : ''; ?>">
                </div>
                
                <button type="submit" class="btn">🔍 Search All Weeks</button>
            </form>
            
            <?php
            if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['search_all']) && isset($_POST['search_phone'])) {
                $searchPhone = trim($_POST['search_phone']);
                $allResults = searchWinnerByPhone($pdo, $searchPhone);
                
                if (!empty($allResults)) {
                    echo '<div class="result info">';
                    echo '<strong>Found ' . count($allResults) . ' result(s) across all weeks:</strong>';
                    echo '<ul class="winner-list" style="margin-top: 10px;">';
                    foreach ($allResults as $result) {
                        echo '<li>';
                        echo '<strong>' . htmlspecialchars($result['name']) . '</strong><br>';
                        echo 'Phone: ' . htmlspecialchars($result['phone']) . '<br>';
                        echo 'Draw Date: ' . $result['draw_date'] . '<br>';
                        echo 'Week Status: ' . ucfirst($result['week_status']) . '<br>';
                        echo 'Claimed: ' . ($result['is_claimed'] ? 'Yes' : 'No');
                        echo '</li>';
                    }
                    echo '</ul>';
                    echo '</div>';
                } else {
                    echo '<div class="result info">';
                    echo '<strong>No results found for this phone number across all weeks.</strong>';
                    echo '</div>';
                }
            }
            ?>
        </div>
    </div>
</body>
</html>
