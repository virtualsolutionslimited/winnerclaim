<?php
require_once 'db.php';
require_once 'winner_functions.php';
require_once 'sms_functions.php';

header('Content-Type: application/json');

// Get the action from the request
$action = $_POST['action'] ?? '';
$phone = $_POST['phone'] ?? '';

// Handle different actions
switch ($action) {
    case 'verify':
        // Check for unclaimed winnings for this specific phone in current draw week
        error_log("API: Verifying phone: " . $phone);
        $unclaimedResult = getUnclaimedWinnerByPhoneForCurrentDraw($pdo, $phone);
        error_log("API: Unclaimed result: " . print_r($unclaimedResult, true));
        
        if ($unclaimedResult['status'] === 'error') {
            echo json_encode([
                'status' => 'error',
                'message' => 'Unable to verify draw information. Please try again.'
            ]);
            exit;
        }
        
        if ($unclaimedResult['status'] === 'not_found' || !$unclaimedResult['unclaimed_winner']) {
            echo json_encode([
                'status' => 'not_found',
                'message' => 'This phone number is not registered as a winner with unclaimed prizes in the current draw week. Please check and try again.'
            ]);
            exit;
        }
        
        // Found unclaimed winner - return winner info
        $winnerInfo = $unclaimedResult['unclaimed_winner'];
        
        echo json_encode([
            'status' => 'success',
            'message' => 'Unclaimed prize found! Click "Send OTP" to receive your verification code.',
            'winner_info' => $winnerInfo,
            'verified' => true,
            'next_step' => 'send_otp'
        ]);
        break;
        
    case 'send_otp':
        // Validate phone number
        if (empty($phone)) {
            echo json_encode([
                'status' => 'error',
                'message' => 'Please enter your MoMo account number'
            ]);
            exit;
        }
        
        // Check for unclaimed winnings for this specific phone in current draw week
        $unclaimedResult = getUnclaimedWinnerByPhoneForCurrentDraw($pdo, $phone);
        
        if ($unclaimedResult['status'] === 'error') {
            echo json_encode([
                'status' => 'error',
                'message' => 'Unable to verify draw information. Please try again.'
            ]);
            exit;
        }
        
        if ($unclaimedResult['status'] === 'not_found' || !$unclaimedResult['unclaimed_winner']) {
            echo json_encode([
                'status' => 'error',
                'message' => 'This phone number is not registered as a winner with unclaimed prizes in the current draw week. Please check and try again.'
            ]);
            exit;
        }
        
        // Get the winner info
        $winnerInfo = $unclaimedResult['unclaimed_winner'];
        
        // Send OTP using the SMS function
        $otpResult = sendOTP($phone, 'claim');
        
        if ($otpResult['status'] === 'success') {
            // Store the OTP in the winners table for verification
            try {
                $stmt = $pdo->prepare("
                    UPDATE winners 
                    SET otp = ?, updatedAt = NOW() 
                    WHERE id = ?
                ");
                $stmt->execute([$otpResult['code'], $winnerInfo['id']]);
                
                echo json_encode([
                    'status' => 'success',
                    'message' => 'Verification code sent to your phone',
                    'winner_info' => $winnerInfo,
                    'otp_sent' => true
                ]);
                
            } catch (PDOException $e) {
                echo json_encode([
                    'status' => 'error',
                    'message' => 'Failed to store verification code. Please try again.'
                ]);
            }
        } else {
            echo json_encode([
                'status' => 'error',
                'message' => 'Failed to send verification code: ' . $otpResult['message']
            ]);
        }
        break;
        
    case 'verify_otp':
        $otp = $_POST['otp'] ?? '';
        
        if (empty($phone) || empty($otp)) {
            echo json_encode([
                'status' => 'error',
                'message' => 'Phone number and OTP are required'
            ]);
            exit;
        }
        
        // Verify the OTP using the SMS function
        $verifyResult = verifyOTPCode($phone, $otp);
        
        if ($verifyResult['status'] === 'success' && $verifyResult['verified']) {
            echo json_encode([
                'status' => 'success',
                'message' => 'Phone number verified successfully',
                'verified' => true,
                'winner_info' => $verifyResult['winner'] ?? null
            ]);
        } else {
            echo json_encode([
                'status' => 'error',
                'message' => $verifyResult['message'] ?? 'Invalid verification code'
            ]);
        }
        break;
        
    default:
        echo json_encode([
            'status' => 'error',
            'message' => 'Invalid action'
        ]);
        break;
}

?>
