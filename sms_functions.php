<?php
require_once 'db.php';

// Include winner_functions for cleanPhoneNumber function
require_once 'winner_functions.php';

/**
 * Generate a 6-digit random code
 * @return string 6-digit code
 */
function generateSixDigitCode() {
    return str_pad(mt_rand(0, 999999), 6, '0', STR_PAD_LEFT);
}

/**
 * Send SMS via Arkesel API
 * @param string $phoneNumber The recipient phone number
 * @param string $message The SMS message
 * @return array Result with status and response
 */
function sendSMS($phoneNumber, $message) {
    $apiKey = 'akR3cGxLb3JwRXpaemFrUFRXR0Y';
    $senderId = 'Raffle';
    
    // Clean phone number - ensure it starts with country code if not present
    $cleanPhone = cleanPhoneNumber($phoneNumber);
    
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
    
    try {
        // Send request
        $response = file_get_contents($fullUrl);
        
        // Log the response
        error_log("SMS API Response: " . $response);
        
        // Parse response
        $result = json_decode($response, true);
        
        error_log("SMS API Response: " . $response);
        error_log("Parsed result: " . print_r($result, true));
        
        // Check for different success indicators
        if ($result && (
            (isset($result['code']) && $result['code'] === 'ok') ||
            (isset($result['status']) && $result['status'] === 'ok') ||
            (isset($result['status']) && strtolower($result['status']) === 'success') ||
            (isset($result['message']) && strpos(strtolower($result['message']), 'success') !== false) ||
            (isset($result['message']) && strpos(strtolower($result['message']), 'sent') !== false)
        )) {
            return [
                'status' => 'success',
                'message' => 'SMS sent successfully',
                'response' => $result,
                'code' => $result['code'] ?? 'unknown'
            ];
        } else {
            return [
                'status' => 'error',
                'message' => 'SMS sending failed',
                'response' => $result,
                'error' => $result['message'] ?? 'Unknown error'
            ];
        }
        
    } catch (Exception $e) {
        error_log("SMS sending exception: " . $e->getMessage());
        return [
            'status' => 'error',
            'message' => 'SMS sending failed: ' . $e->getMessage(),
            'error' => $e->getMessage()
        ];
    }
}

/**
 * Generate and send 6-digit code via SMS
 * @param string $phoneNumber The recipient phone number
 * @param string $purpose The purpose of the code (e.g., 'verification', 'claim')
 * @return array Result with status, code, and SMS response
 */
function sendOTP($phoneNumber, $purpose = 'verification') {
    // Generate 6-digit code
    $code = generateSixDigitCode();
    
    // Create message based on purpose
    switch ($purpose) {
        case 'claim':
            $message = "Your Raffle claim code is: {$code}. This code expires in 10 minutes. Do not share this code with anyone.";
            break;
        case 'verification':
            $message = "Your Raffle verification code is: {$code}. This code expires in 10 minutes. Do not share this code with anyone.";
            break;
        default:
            $message = "Your Raffle code is: {$code}. This code expires in 10 minutes. Do not share this code with anyone.";
    }
    
    // Send SMS
    $smsResult = sendSMS($phoneNumber, $message);
    
    if ($smsResult['status'] === 'success') {
        // Update OTP field for winner if this is for current week
        $updated = updateWinnerOTP($phoneNumber, $code);
        
        return [
            'status' => 'success',
            'message' => 'OTP code sent successfully',
            'code' => $code, // Return code for testing purposes (remove in production)
            'sms_response' => $smsResult['response'],
            'winner_updated' => $updated
        ];
    } else {
        return [
            'status' => 'error',
            'message' => 'Failed to send OTP code',
            'error' => $smsResult['error'],
            'sms_response' => $smsResult['response'] ?? null
        ];
    }
}

/**
 * Update OTP field for winner in current week
 * @param string $phoneNumber The phone number
 * @param string $code The OTP code
 * @return bool Success status
 */
function updateWinnerOTP($phoneNumber, $code) {
    global $pdo;
    
    try {
        require_once 'draw_functions.php';
        
        // Clean phone number for matching
        $cleanPhone = cleanPhoneNumber($phoneNumber);
        
        // Get current draw week
        $currentDraw = getCurrentDrawWeek($pdo);
        
        if (!$currentDraw) {
            error_log("Cannot update winner OTP: No current draw week found");
            return false;
        }
        
        // Update OTP for winner in current draw week
        $stmt = $pdo->prepare("
            UPDATE winners 
            SET otp = ? 
            WHERE draw_week = ? AND (
                phone = ? OR
                phone = ? OR
                REPLACE(phone, ' ', '') = ? OR
                REPLACE(phone, '-', '') = ? OR
                REPLACE(phone, '(', '') = ?
            )
        ");
        
        $searchPatterns = [
            $phoneNumber,                           // Original input
            $cleanPhone,                           // Cleaned with country code
            $phoneNumber,                           // Original without spaces
            $phoneNumber,                           // Original without dashes
            $phoneNumber                            // Original without parentheses
        ];
        
        $stmt->execute([$code, $currentDraw['id'], ...$searchPatterns]);
        
        $affectedRows = $stmt->rowCount();
        
        // Check if winner exists but OTP update didn't change anything (same OTP or no rows affected)
        if ($affectedRows === 0) {
            // Try to find if winner exists with any OTP value
            $checkStmt = $pdo->prepare("
                SELECT id, otp FROM winners 
                WHERE draw_week = ? AND (
                    phone = ? OR
                    phone = ? OR
                    REPLACE(phone, ' ', '') = ? OR
                    REPLACE(phone, '-', '') = ? OR
                    REPLACE(phone, '(', '') = ?
                )
            ");
            $checkStmt->execute([$currentDraw['id'], ...$searchPatterns]);
            $existingWinner = $checkStmt->fetch(PDO::FETCH_ASSOC);
            
            if ($existingWinner) {
                error_log("Winner found (ID: {$existingWinner['id']}, Current OTP: {$existingWinner['otp']})");
                return true; // Winner exists, treat as successful
            }
        }
        
        error_log("Updated OTP for {$affectedRows} winner(s) with phone {$phoneNumber}");
        
        return $affectedRows > 0 || ($affectedRows === 0 && isset($existingWinner));
        
    } catch (PDOException $e) {
        error_log("Error updating winner OTP: " . $e->getMessage());
        return false;
    }
}

/**
 * Verify OTP code from winners table
 * @param string $phoneNumber The phone number
 * @param string $code The code to verify
 * @return array Verification result
 */
function verifyOTPCode($phoneNumber, $code) {
    global $pdo;
    
    try {
        require_once 'draw_functions.php';
        
        $cleanPhone = cleanPhoneNumber($phoneNumber);
        
        // Get current draw week
        $currentDraw = getCurrentDrawWeek($pdo);
        
        if (!$currentDraw) {
            return [
                'status' => 'error',
                'message' => 'No current draw week found',
                'verified' => false
            ];
        }
        
        // Check OTP in winners table for current week
        $stmt = $pdo->prepare("
            SELECT * FROM winners 
            WHERE draw_week = ? AND otp = ? AND (
                phone = ? OR
                phone = ? OR
                REPLACE(phone, ' ', '') = ? OR
                REPLACE(phone, '-', '') = ? OR
                REPLACE(phone, '(', '') = ?
            )
        ");
        
        $searchPatterns = [
            $phoneNumber,                           // Original input
            $cleanPhone,                           // Cleaned with country code
            $phoneNumber,                           // Original without spaces
            $phoneNumber,                           // Original without dashes
            $phoneNumber                            // Original without parentheses
        ];
        
        $stmt->execute([$currentDraw['id'], $code, ...$searchPatterns]);
        $winner = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($winner) {
            // Clear OTP after successful verification
            $clearStmt = $pdo->prepare("UPDATE winners SET otp = NULL WHERE id = ?");
            $clearStmt->execute([$winner['id']]);
            
            return [
                'status' => 'success',
                'message' => 'Code verified successfully',
                'verified' => true,
                'winner' => [
                    'id' => $winner['id'],
                    'name' => $winner['name'],
                    'phone' => $winner['phone']
                ]
            ];
        } else {
            return [
                'status' => 'error',
                'message' => 'Invalid or expired code',
                'verified' => false
            ];
        }
        
    } catch (PDOException $e) {
        error_log("Error verifying OTP code: " . $e->getMessage());
        return [
            'status' => 'error',
            'message' => 'Verification failed: ' . $e->getMessage(),
            'verified' => false
        ];
    }
}

/**
 * Resend OTP code to phone number
 * @param string $phoneNumber The phone number to resend OTP to
 * @param string $purpose The purpose of the OTP (verification, claim, etc.)
 * @return array Result of the resend operation
 */
function resendOTP($phoneNumber, $purpose = 'verification') {
    try {
        // Generate new OTP code
        $code = generateSixDigitCode();
        
        // Clean phone number for SMS
        $cleanPhone = cleanPhoneNumber($phoneNumber);
        
        // Prepare message
        $message = "Your Raffle verification code is: $code";
        if ($purpose === 'claim') {
            $message = "Your Raffle prize claim code is: $code";
        }
        
        // Send SMS
        $smsResult = sendSMS($cleanPhone, $message);
        
        if ($smsResult['status'] === 'success') {
            // Update OTP in winners table
            $updated = updateWinnerOTP($phoneNumber, $code);
            
            if ($updated) {
                return [
                    'status' => 'success',
                    'message' => 'OTP code resent successfully',
                    'code' => $code,
                    'sms_response' => $smsResult['response'],
                    'winner_updated' => true
                ];
            } else {
                return [
                    'status' => 'error',
                    'message' => 'SMS sent but failed to update winner record',
                    'code' => $code,
                    'sms_response' => $smsResult['response'],
                    'winner_updated' => false
                ];
            }
        } else {
            return [
                'status' => 'error',
                'message' => 'Failed to resend OTP code',
                'error' => $smsResult['error'],
                'sms_response' => $smsResult['response'],
                'winner_updated' => false
            ];
        }
        
    } catch (Exception $e) {
        error_log("Error resending OTP: " . $e->getMessage());
        return [
            'status' => 'error',
            'message' => 'Failed to resend OTP code',
            'error' => $e->getMessage(),
            'winner_updated' => false
        ];
    }
}

?>
