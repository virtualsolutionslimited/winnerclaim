<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SMS OTP Test - Raffle System</title>
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
        
        input[type="text"], select {
            width: 100%;
            padding: 12px 15px;
            border: 2px solid #e1e5e9;
            border-radius: 8px;
            font-size: 1rem;
            transition: all 0.3s ease;
        }
        
        input[type="text"]:focus, select:focus {
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
            margin-right: 10px;
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
        
        .code-display {
            font-family: 'Courier New', monospace;
            font-size: 2rem;
            font-weight: bold;
            text-align: center;
            padding: 20px;
            background: #f8f9fa;
            border: 2px dashed #667eea;
            border-radius: 10px;
            margin: 15px 0;
            letter-spacing: 5px;
        }
        
        .two-column {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            margin-top: 20px;
        }
        
        .api-info {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 8px;
            margin-top: 20px;
            font-size: 0.9rem;
        }
        
        .api-info code {
            background: #e9ecef;
            padding: 2px 5px;
            border-radius: 3px;
            font-family: 'Courier New', monospace;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="card">
            <h1>📱 SMS OTP Test - Raffle System</h1>
            
            <form method="post">
                <div class="form-group">
                    <label for="phone">Phone Number:</label>
                    <input type="text" name="phone" id="phone" 
                           placeholder="e.g., 0201234567, 233201234567"
                           value="<?php echo isset($_POST['phone']) ? htmlspecialchars($_POST['phone']) : ''; ?>"
                           required>
                </div>
                
                <div class="form-group">
                    <label for="purpose">Purpose:</label>
                    <select name="purpose" id="purpose">
                        <option value="verification" <?php echo (isset($_POST['purpose']) && $_POST['purpose'] === 'verification') ? 'selected' : ''; ?>>Phone Verification</option>
                        <option value="claim" <?php echo (isset($_POST['purpose']) && $_POST['purpose'] === 'claim') ? 'selected' : ''; ?>>Prize Claim</option>
                    </select>
                </div>
                
                <button type="submit" name="send_otp" class="btn">📤 Send OTP Code</button>
                <button type="submit" name="generate_test" class="btn btn-secondary">🧪 Generate Test Code Only</button>
            </form>
            
            <?php
            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                require_once 'sms_functions.php';
                
                if (isset($_POST['send_otp'])) {
                    $phone = trim($_POST['phone']);
                    $purpose = $_POST['purpose'];
                    
                    $result = sendOTP($phone, $purpose);
                    
                    echo '<div class="result ' . $result['status'] . '">';
                    echo '<strong>' . $result['message'] . '</strong>';
                    
                    if ($result['status'] === 'success') {
                        echo '<div class="code-display">' . $result['code'] . '</div>';
                        echo '<small><strong>Test Note:</strong> Code is shown above for testing. In production, remove the code from the response.</small>';
                        
                        if (isset($result['winner_updated'])) {
                            echo '<p><strong>Winner Record Updated:</strong> ' . ($result['winner_updated'] ? '✅ Yes' : '❌ No') . '</p>';
                        }
                        
                        if (isset($result['sms_response'])) {
                            echo '<div class="api-info">';
                            echo '<strong>API Response:</strong><br>';
                            echo '<pre>' . htmlspecialchars(json_encode($result['sms_response'], JSON_PRETTY_PRINT)) . '</pre>';
                            echo '</div>';
                        }
                    } else {
                        if (isset($result['error'])) {
                            echo '<br><small>Error: ' . htmlspecialchars($result['error']) . '</small>';
                        }
                        
                        if (isset($result['sms_response'])) {
                            echo '<div class="api-info">';
                            echo '<strong>Full API Response:</strong><br>';
                            echo '<pre>' . htmlspecialchars(json_encode($result['sms_response'], JSON_PRETTY_PRINT)) . '</pre>';
                            echo '</div>';
                        }
                    }
                    
                    echo '</div>';
                }
                
                if (isset($_POST['generate_test'])) {
                    $phone = trim($_POST['phone']);
                    $purpose = $_POST['purpose'];
                    
                    $code = generateSixDigitCode();
                    $cleanPhone = cleanPhoneNumber($phone);
                    
                    echo '<div class="result info">';
                    echo '<strong>Test Code Generated (Not Sent)</strong>';
                    echo '<div class="code-display">' . $code . '</div>';
                    echo '<small>Phone: ' . htmlspecialchars($cleanPhone) . '</small>';
                    echo '</div>';
                }
            }
            ?>
        </div>
        
        <div class="card">
            <h2>🔍 Verify OTP Code</h2>
            
            <form method="post">
                <div class="form-group">
                    <label for="verify_phone">Phone Number:</label>
                    <input type="text" name="verify_phone" id="verify_phone" 
                           placeholder="Enter phone number to verify"
                           value="<?php echo isset($_POST['verify_phone']) ? htmlspecialchars($_POST['verify_phone']) : ''; ?>">
                </div>
                
                <div class="form-group">
                    <label for="verify_code">6-Digit Code:</label>
                    <input type="text" name="verify_code" id="verify_code" 
                           placeholder="Enter the 6-digit code"
                           maxlength="6"
                           value="<?php echo isset($_POST['verify_code']) ? htmlspecialchars($_POST['verify_code']) : ''; ?>">
                </div>
                
                <button type="submit" name="verify_otp" class="btn">✅ Verify Code</button>
            </form>
            
            <?php
            if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['verify_otp'])) {
                $phone = trim($_POST['verify_phone']);
                $code = trim($_POST['verify_code']);
                
                if (!empty($phone) && !empty($code)) {
                    $result = verifyOTPCode($phone, $code);
                    
                    echo '<div class="result ' . $result['status'] . '">';
                    echo '<strong>' . $result['message'] . '</strong>';
                    echo '</div>';
                } else {
                    echo '<div class="result error">';
                    echo '<strong>Please enter both phone number and code</strong>';
                    echo '</div>';
                }
            }
            ?>
        </div>
        
        <div class="card">
            <h2>📊 API Information</h2>
            
            <div class="api-info">
                <h3>Arkesel SMS API Configuration:</h3>
                <ul>
                    <li><strong>Endpoint:</strong> <code>https://sms.arkesel.com/sms/api</code></li>
                    <li><strong>API Key:</strong> <code>akR3cGxLb3JwRXpaemFrUFRXR0Y</code></li>
                    <li><strong>Sender ID:</strong> <code>Raffle</code></li>
                    <li><strong>Parameters:</strong> action, api_key, to, from, sms</li>
                </ul>
                
                <h3>Features:</h3>
                <ul>
                    <li>✅ 6-digit random code generation</li>
                    <li>✅ Phone number cleaning (adds +233 prefix)</li>
                    <li>✅ Code storage in database with 10-minute expiry</li>
                    <li>✅ Verification functionality</li>
                    <li>✅ Different purposes (verification, claim)</li>
                    <li>✅ Automatic cleanup of used codes</li>
                </ul>
                
                <h3>Database Table Created:</h3>
                <code>otp_codes (id, phone, code, purpose, expires_at, is_used, used_at, created_at)</code>
            </div>
        </div>
    </div>
</body>
</html>
