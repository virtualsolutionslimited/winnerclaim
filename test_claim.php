<?php
require_once 'db.php';
require_once 'winner_functions.php';

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Claim - Raffle System</title>
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
        
        input[type="text"], input[type="email"], input[type="password"], input[type="file"] {
            width: 100%;
            padding: 12px 15px;
            border: 2px solid #e1e5e9;
            border-radius: 8px;
            font-size: 1rem;
            transition: all 0.3s ease;
        }
        
        input[type="text"]:focus, input[type="email"]:focus, input[type="password"]:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }
        
        .required {
            color: #dc3545;
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
        
        .btn-secondary {
            background: #6c757d;
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
        
        .field-info {
            font-size: 0.85rem;
            color: #6c757d;
            margin-top: 5px;
        }
        
        .two-column {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
        }
        
        @media (max-width: 600px) {
            .two-column {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="card">
            <h1>🏆 Create Claim - Raffle System</h1>
            <p style="color: #666; margin-bottom: 20px;">Complete your prize claim with additional details</p>
            
            <form method="post" enctype="multipart/form-data">
                <div class="form-group">
                    <label for="phone">Phone Number <span class="required">*</span></label>
                    <input type="text" name="phone" id="phone" 
                           placeholder="e.g., 0201234567, 233201234567"
                           value="<?php echo isset($_POST['phone']) ? htmlspecialchars($_POST['phone']) : ''; ?>"
                           required>
                    <div class="field-info">Phone number must match a winner for the selected draw week</div>
                </div>
                
                <div class="form-group">
                    <label for="draw_week_id">Draw Week ID <span class="required">*</span></label>
                    <input type="number" name="draw_week_id" id="draw_week_id" 
                           placeholder="e.g., 20"
                           value="<?php echo isset($_POST['draw_week_id']) ? htmlspecialchars($_POST['draw_week_id']) : '20'; ?>"
                           required>
                    <div class="field-info">Enter the Draw Week ID (e.g., 20 for Nov 2, 2025)</div>
                </div>
                
                <div class="two-column">
                    <div class="form-group">
                        <label for="email">Email Address</label>
                        <input type="email" name="email" id="email" 
                               placeholder="your@email.com"
                               value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>">
                    </div>
                    
                    <div class="form-group">
                        <label for="password">Password</label>
                        <input type="password" name="password" id="password" 
                               placeholder="Create a password"
                               value="<?php echo isset($_POST['password']) ? htmlspecialchars($_POST['password']) : ''; ?>">
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="photo">Profile Photo</label>
                    <input type="file" name="photo" id="photo" accept="image/*">
                    <div class="field-info">Upload your profile picture (optional)</div>
                </div>
                
                <div class="form-group">
                    <label for="ghanacard_number">Ghana Card Number</label>
                    <input type="text" name="ghanacard_number" id="ghanacard_number" 
                           placeholder="GHA-XXXXXXXXXXX"
                           value="<?php echo isset($_POST['ghanacard_number']) ? htmlspecialchars($_POST['ghanacard_number']) : ''; ?>">
                    <div class="field-info">Your Ghana Card ID number (optional)</div>
                </div>
                
                <div class="form-group">
                    <label for="ghanacard_photo">Ghana Card Photo</label>
                    <input type="file" name="ghanacard_photo" id="ghanacard_photo" accept="image/*">
                    <div class="field-info">Upload a photo of your Ghana Card (optional)</div>
                </div>
                
                <button type="submit" name="create_claim" class="btn">📝 Submit Claim</button>
                <button type="submit" name="check_winner" class="btn btn-secondary">🔍 Check Winner Status</button>
            </form>
            
            <?php
            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                if (isset($_POST['create_claim'])) {
                    $phone = trim($_POST['phone']);
                    $drawWeekId = isset($_POST['draw_week_id']) ? (int)$_POST['draw_week_id'] : null;
                    
                    if (!empty($phone) && !empty($drawWeekId)) {
                        // Handle file uploads
                        $photoPath = null;
                        $ghanacardPhotoPath = null;
                        
                        if (isset($_FILES['photo']) && $_FILES['photo']['error'] === UPLOAD_ERR_OK) {
                            $uploadDir = 'uploads/photos/';
                            if (!is_dir($uploadDir)) {
                                mkdir($uploadDir, 0755, true);
                            }
                            $photoPath = $uploadDir . uniqid() . '_' . basename($_FILES['photo']['name']);
                            move_uploaded_file($_FILES['photo']['tmp_name'], $photoPath);
                        }
                        
                        if (isset($_FILES['ghanacard_photo']) && $_FILES['ghanacard_photo']['error'] === UPLOAD_ERR_OK) {
                            $uploadDir = 'uploads/ghanacards/';
                            if (!is_dir($uploadDir)) {
                                mkdir($uploadDir, 0755, true);
                            }
                            $ghanacardPhotoPath = $uploadDir . uniqid() . '_' . basename($_FILES['ghanacard_photo']['name']);
                            move_uploaded_file($_FILES['ghanacard_photo']['tmp_name'], $ghanacardPhotoPath);
                        }
                        
                        // Prepare claim data
                        $claimData = [
                            'phone' => $phone,
                            'email' => !empty($_POST['email']) ? trim($_POST['email']) : null,
                            'password' => !empty($_POST['password']) ? trim($_POST['password']) : null,
                            'photo' => $photoPath,
                            'ghanacard_number' => !empty($_POST['ghanacard_number']) ? trim($_POST['ghanacard_number']) : null,
                            'ghanacard_photo' => $ghanacardPhotoPath
                        ];
                        
                        // Create claim for specific draw week
                        $result = createClaimForDrawWeek($pdo, $claimData, $drawWeekId);
                        
                        echo '<div class="result ' . $result['status'] . '">';
                        echo '<strong>' . $result['message'] . '</strong>';
                        
                        if ($result['status'] === 'success') {
                            echo '<br><small>Winner ID: ' . $result['winner_id'] . '</small>';
                            echo '<br><small>Draw Week ID: ' . $result['draw_week_id'] . '</small>';
                            if (isset($result['draw_details'])) {
                                echo '<br><small>Draw Date: ' . date('F j, Y \a\t g:i A', strtotime($result['draw_details']['date'])) . '</small>';
                            }
                            
                            // Show SMS notification status
                            if (isset($result['sms_sent'])) {
                                echo '<br><br><strong>SMS Notification:</strong> ';
                                if ($result['sms_sent']) {
                                    echo '<span style="color: #28a745;">✅ Success notification sent to ' . htmlspecialchars($phone) . '</span>';
                                } else {
                                    echo '<span style="color: #dc3545;">❌ Failed to send SMS notification</span>';
                                    if (isset($result['sms_result']['message'])) {
                                        echo '<br><small>Error: ' . htmlspecialchars($result['sms_result']['message']) . '</small>';
                                    }
                                }
                            }
                        }
                        
                        echo '</div>';
                    } else {
                        echo '<div class="result error">';
                        echo '<strong>Please provide both phone number and draw week ID</strong>';
                        echo '</div>';
                    }
                }
                
                if (isset($_POST['check_winner'])) {
                    $phone = trim($_POST['phone']);
                    $drawWeekId = isset($_POST['draw_week_id']) ? (int)$_POST['draw_week_id'] : null;
                    
                    if (!empty($phone) && !empty($drawWeekId)) {
                        // Check winner for specific draw week
                        $cleanPhone = preg_replace('/[\s\-\(\)]/', '', $phone);
                        
                        $stmt = $pdo->prepare("
                            SELECT w.*, d.date as draw_date
                            FROM winners w 
                            LEFT JOIN draw_dates d ON w.draw_week = d.id 
                            WHERE w.draw_week = ? AND (
                                REPLACE(REPLACE(REPLACE(w.phone, ' ', ''), '-', ''), '(', '') = ? OR
                                REPLACE(REPLACE(REPLACE(w.phone, ' ', ''), '-', ''), '(', '') LIKE ? OR
                                w.phone LIKE ? OR
                                w.phone = ?
                            )
                        ");
                        
                        $searchPatterns = [
                            $cleanPhone,
                            $cleanPhone . '%',
                            '%' . $phone . '%',
                            $phone
                        ];
                        
                        $stmt->execute([$drawWeekId, ...$searchPatterns]);
                        $winner = $stmt->fetch(PDO::FETCH_ASSOC);
                        
                        echo '<div class="result ' . ($winner ? 'success' : 'error') . '">';
                        echo '<strong>' . ($winner ? 'Winner found!' : 'Winner not found') . '</strong>';
                        
                        if ($winner) {
                            echo '<br><small>Winner: ' . htmlspecialchars($winner['name']) . '</small>';
                            echo '<br><small>Phone: ' . htmlspecialchars($winner['phone']) . '</small>';
                            echo '<br><small>Draw Date: ' . ($winner['draw_date'] ? date('F j, Y \a\t g:i A', strtotime($winner['draw_date'])) : 'N/A') . '</small>';
                            echo '<br><small>Status: ' . ($winner['is_claimed'] ? '✅ Claimed' : '⏳ Pending Claim') . '</small>';
                        }
                        
                        echo '</div>';
                    } else {
                        echo '<div class="result error">';
                        echo '<strong>Please provide both phone number and draw week ID</strong>';
                        echo '</div>';
                    }
                }
            }
            ?>
        </div>
        
        <div class="card">
            <h3>ℹ️ How to Use</h3>
            <ul style="color: #666; line-height: 1.6;">
                <li><strong>Phone Number (Required):</strong> Must match a winner for the current draw week</li>
                <li><strong>Email & Password:</strong> Optional - for account creation</li>
                <li><strong>Photos:</strong> Optional - profile picture and Ghana Card verification</li>
                <li><strong>Check Winner Status:</strong> Verify if phone number is a winner before claiming</li>
                <li>Once submitted, <code>is_claimed</code> will be set to <strong>1</strong></li>
            </ul>
        </div>
    </div>
</body>
</html>
