<?php
session_start();

require_once 'db.php';
require_once 'winner_functions.php';
require_once 'draw_functions.php';

header('Content-Type: application/json');

// Get the action from the request
$action = $_POST['action'] ?? '';

try {
    switch ($action) {
        case 'create_claim':
            // Check if session data exists
            if (!isset($_SESSION['account_data']) || !isset($_SESSION['winner_info'])) {
                echo json_encode([
                    'status' => 'error',
                    'message' => 'Session expired. Please start over.'
                ]);
                exit;
            }
            
            // Get additional claim data from request
            $ghanaCard = $_POST['ghana_card'] ?? '';
            $selfieImage = $_FILES['selfie_image'] ?? null;
            
            // Validate required fields
            if (empty($ghanaCard)) {
                echo json_encode([
                    'status' => 'error',
                    'message' => 'Ghana Card number is required'
                ]);
                exit;
            }
            
            // Prepare claim data with session data
            $claimData = [
                'phone' => $_SESSION['account_data']['phone'],
                'email' => $_SESSION['account_data']['email'],
                'password' => $_SESSION['account_data']['password'], // In production, this should be hashed
                'ghana_card' => $ghanaCard,
                'selfie_image' => $selfieImage,
                'is_account_holder' => $_SESSION['account_data']['is_account_holder'],
                'terms_agreement' => $_SESSION['account_data']['terms_agreement'],
                'privacy_agreement' => $_SESSION['account_data']['privacy_agreement'],
                'account_created_at' => $_SESSION['account_data']['created_at']
            ];
            
            // Handle selfie upload if provided
            if ($selfieImage && $selfieImage['error'] === UPLOAD_ERR_OK) {
                $uploadDir = 'uploads/photos/';
                if (!file_exists($uploadDir)) {
                    mkdir($uploadDir, 0755, true);
                }
                
                $fileName = uniqid() . '_' . basename($selfieImage['name']);
                $targetPath = $uploadDir . $fileName;
                
                if (move_uploaded_file($selfieImage['tmp_name'], $targetPath)) {
                    $claimData['selfie_path'] = $targetPath;
                } else {
                    echo json_encode([
                        'status' => 'error',
                        'message' => 'Failed to upload selfie image'
                    ]);
                    exit;
                }
            }
            
            // Create the claim using the winner_functions.php
            $pdo = connectDB();
            $result = createClaim($pdo, $claimData);
            
            if ($result['status'] === 'success') {
                // Clear session after successful claim
                unset($_SESSION['account_data']);
                unset($_SESSION['winner_info']);
                
                echo json_encode([
                    'status' => 'success',
                    'message' => 'Claim created successfully',
                    'claim_id' => $result['claim_id'] ?? null,
                    'contract_url' => $result['contract_url'] ?? null
                ]);
            } else {
                echo json_encode($result);
            }
            break;
            
        case 'get_session_data':
            // For debugging - return current session data
            echo json_encode([
                'status' => 'success',
                'account_data' => $_SESSION['account_data'] ?? null,
                'winner_info' => $_SESSION['winner_info'] ?? null
            ]);
            break;
            
        default:
            echo json_encode([
                'status' => 'error',
                'message' => 'Invalid action'
            ]);
            break;
    }
} catch (Exception $e) {
    echo json_encode([
        'status' => 'error',
        'message' => 'An error occurred: ' . $e->getMessage()
    ]);
}
?>
