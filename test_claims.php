<?php
require_once 'db.php';
require_once 'winner_functions.php';

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Claims Search - Raffle System</title>
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
            max-width: 1000px;
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
        
        .claims-table {
            width: 100%;
            margin-top: 20px;
            border-collapse: collapse;
        }
        
        .claims-table th,
        .claims-table td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #dee2e6;
        }
        
        .claims-table th {
            background-color: #f8f9fa;
            font-weight: 600;
            color: #495057;
        }
        
        .claims-table tr:hover {
            background-color: #f8f9fa;
        }
        
        .status-badge {
            padding: 4px 8px;
            border-radius: 12px;
            font-size: 0.75rem;
            font-weight: 600;
            text-transform: uppercase;
        }
        
        .status-claimed {
            background-color: #28a745;
            color: white;
        }
        
        .status-past {
            background-color: #6c757d;
            color: white;
        }
        
        .status-recent {
            background-color: #17a2b8;
            color: white;
        }
        
        .status-upcoming {
            background-color: #ffc107;
            color: #212529;
        }
        
        .no-claims {
            text-align: center;
            padding: 40px;
            color: #6c757d;
            font-style: italic;
        }
        
        .search-info {
            background: #e9ecef;
            padding: 10px 15px;
            border-radius: 5px;
            margin-bottom: 15px;
            font-size: 0.9rem;
        }
        
        .claim-details {
            font-size: 0.85rem;
            color: #6c757d;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="card">
            <h1>🏆 Claims Search - Raffle System</h1>
            <p style="color: #666; margin-bottom: 20px;">Search for all claimed prizes by phone number</p>
            
            <form method="post">
                <div class="form-group">
                    <label for="phone">Phone Number:</label>
                    <input type="text" name="phone" id="phone" 
                           placeholder="e.g., 0201234567, 233201234567, +233201234567"
                           value="<?php echo isset($_POST['phone']) ? htmlspecialchars($_POST['phone']) : ''; ?>"
                           required>
                </div>
                
                <button type="submit" name="search_claims" class="btn">🔍 Search Claims</button>
            </form>
            
            <?php
            if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['search_claims'])) {
                $phone = trim($_POST['phone']);
                
                if (!empty($phone)) {
                    $result = getClaimsByPhone($pdo, $phone);
                    
                    echo '<div class="result ' . $result['status'] . '">';
                    
                    if ($result['status'] === 'success') {
                        echo '<div class="search-info">';
                        echo '<strong>Phone searched:</strong> ' . htmlspecialchars($result['phone_searched']);
                        echo '<br><strong>Total claims found:</strong> ' . $result['total_claims'];
                        echo '</div>';
                        
                        if ($result['total_claims'] > 0) {
                            echo '<h3 style="margin: 20px 0 10px 0;">Claim History</h3>';
                            echo '<table class="claims-table">';
                            echo '<thead>';
                            echo '<tr>';
                            echo '<th>Name</th>';
                            echo '<th>Phone</th>';
                            echo '<th>Draw Date</th>';
                            echo '<th>Week Status</th>';
                            echo '<th>Claimed At</th>';
                            echo '<th>Status</th>';
                            echo '</tr>';
                            echo '</thead>';
                            echo '<tbody>';
                            
                            foreach ($result['claims'] as $claim) {
                                echo '<tr>';
                                echo '<td>' . htmlspecialchars($claim['name']) . '</td>';
                                echo '<td>' . htmlspecialchars($claim['phone']) . '</td>';
                                echo '<td>' . date('M j, Y', strtotime($claim['draw_date'])) . '</td>';
                                echo '<td><span class="status-badge status-' . $claim['week_status'] . '">' . $claim['week_status'] . '</span></td>';
                                echo '<td>' . date('M j, Y H:i', strtotime($claim['updatedAt'])) . '</td>';
                                echo '<td><span class="status-badge status-claimed">Claimed</span></td>';
                                echo '</tr>';
                                
                                // Show additional details in a second row
                                echo '<tr>';
                                echo '<td colspan="6" class="claim-details">';
                                echo '<strong>Winner ID:</strong> ' . $claim['id'] . ' | ';
                                echo '<strong>Draw Week:</strong> ' . $claim['draw_week'] . ' | ';
                                echo '<strong>Created:</strong> ' . date('M j, Y H:i', strtotime($claim['createdAt']));
                                echo '</td>';
                                echo '</tr>';
                            }
                            
                            echo '</tbody>';
                            echo '</table>';
                        } else {
                            echo '<div class="no-claims">';
                            echo '📭 No claims found for phone number: ' . htmlspecialchars($phone);
                            echo '<br><small>This phone number has not claimed any prizes yet.</small>';
                            echo '</div>';
                        }
                    } else {
                        echo '<strong>Error:</strong> ' . htmlspecialchars($result['message']);
                    }
                    
                    echo '</div>';
                }
            }
            ?>
        </div>
        
        <div class="card">
            <h3>ℹ️ How to Use</h3>
            <ul style="color: #666; line-height: 1.6;">
                <li>Enter any phone number format (with/without country code, spaces, dashes)</li>
                <li>Click "🔍 Search Claims" to find all claimed prizes for that phone</li>
                <li>Results show the claim history with draw dates and claim timestamps</li>
                <li>Only <strong>claimed</strong> prizes are shown (not unclaimed winners)</li>
            </ul>
        </div>
    </div>
</body>
</html>
